# -*- coding: utf-8 -*-
"""Parse CMT practicum guide text into procedures JSON for Laravel import."""
import argparse
import json
import re
from pathlib import Path

BASE = Path(__file__).resolve().parents[1]
GUIDES_DIR = BASE / "storage" / "app" / "public" / "clinical-guides"

LEVELS = {
    "4": {
        "pdf": "CMT 4 TUTORS  PRACTICUM GUIDE FINAL.pdf",
        "text": BASE / "storage" / "app" / "clinical-guide-l4-full-text.txt",
        "json": BASE / "storage" / "app" / "cmt4-practicum-import.json",
        "source": "CMT 4 Tutors Practicum Guide (FINAL) — parsed",
        "nta_level": 4,
        "module_markers": [
            ("CMT04101", "Communication Skills", "semester_one", None),
            ("CMT04102", "Human Anatomy", "semester_i", None),
            ("CMT04104", "Epidemiology", "semester_i", None),
            ("CMT04105", "Computer Applications", "semester_i", None),
            ("CMT04107", "Microbiology", "semester_i", None),
            ("CMT04208", "Clinical Nutrition", "clinical_nutrition", "CMT04208"),
            ("CMT04209", "Clinical Skills", "patient_care", "CMT04209"),
            ("CMT04211", "Clinical Laboratory", "clinical_laboratory", "CMT04211"),
            ("CMT04212", "Patient Care", "patient_care", "CMT04212"),
        ],
    },
    "5": {
        "pdf": "PRACTICUM GUIDE NTA L5_Tutors.pdf",
        "text": BASE / "storage" / "app" / "clinical-guide-l5-full-text.txt",
        "json": BASE / "storage" / "app" / "cmt5-practicum-import.json",
        "source": "CMT NTA Level 5 Tutors Practicum Guide — parsed",
        "nta_level": 5,
        "module_markers": [],
    },
    "6": {
        "pdf": "NTA LEVEL 6 PG FOR TUTORS REVIEWED OCTOBER 2022- (1).pdf",
        "text": BASE / "storage" / "app" / "clinical-guide-l6-full-text.txt",
        "json": BASE / "storage" / "app" / "cmt6-practicum-import.json",
        "source": "CMT NTA Level 6 Tutors Practicum Guide — parsed",
        "nta_level": 6,
        "module_markers": [],
    },
}

DEFAULT_ASSESSMENT = "Practical, Checklist, Logbook / procedure record (instructor sign-off)"

DEPT_KEYWORDS = [
    ("clinical_nutrition", ["nutrition", "diet", "feeding"]),
    ("clinical_laboratory", ["laboratory", "lab ", "specimen", "stain", "microscopy"]),
    ("internal_medicine", ["internal medicine", "medical ward", "adult patient"]),
    ("surgery", ["surgery", "surgical", "theatre", "wound", "sutur"]),
    ("obstetrics_gynaecology", ["obstetric", "gynaecology", "gynecology", "antenatal", "delivery", "postnatal", "labour"]),
    ("paediatrics", ["paediatric", "pediatric", "child health", "imnci", "immunization", "neonatal"]),
    ("community_health", ["community", "outreach", "surveillance", "health education"]),
    ("patient_care", ["patient care", "nursing", "bed bath", "vital sign", "medication"]),
]


def department_for_checklist_l4(num: int) -> str:
    if num <= 20:
        return "semester_one"
    if num <= 23:
        return "clinical_nutrition"
    if num <= 32:
        return "patient_care"
    if num <= 48:
        return "clinical_laboratory"
    return "patient_care"


def department_from_title(title: str, level: int, num: int) -> str:
    t = title.lower()
    for dept, keys in DEPT_KEYWORDS:
        if any(k in t for k in keys):
            return dept
    if level == 4:
        return department_for_checklist_l4(num)
    if level == 5:
        if num <= 15:
            return "semester_one"
        if num <= 25:
            return "internal_medicine"
        if num <= 35:
            return "surgery"
        if num <= 45:
            return "obstetrics_gynaecology"
        return "community_health"
    if level == 6:
        if num <= 12:
            return "internal_medicine"
        if num <= 24:
            return "surgery"
        if num <= 36:
            return "obstetrics_gynaecology"
        return "community_health"
    return "patient_care"


def discover_module_markers(text: str) -> list:
    seen = {}
    for m in re.finditer(
        r"Practicum Module:\s*(CMT\s*0?(\d+))\s*([^\n]+)?",
        text,
        re.IGNORECASE,
    ):
        mod = re.sub(r"\s+", "", m.group(1).upper())
        if mod in seen:
            continue
        title = (m.group(3) or "").strip()
        dept = department_from_title(title, 5, 0)
        seen[mod] = (mod, title, dept, mod)
    return list(seen.values())


def parse_level(level_key: str) -> None:
    cfg = LEVELS[level_key]
    text_path: Path = cfg["text"]
    if not text_path.is_file() and level_key == "4":
        legacy = BASE / "storage" / "app" / "clinical-guide-full-text.txt"
        if legacy.is_file():
            text_path = legacy
    if not text_path.is_file():
        raise SystemExit(f"Text not found: {text_path}. Run: py scripts/extract_practicum_pdf.py --level {level_key}")

    text = text_path.read_text(encoding="utf-8", errors="replace")
    nta = int(cfg["nta_level"])
    markers = cfg["module_markers"] or discover_module_markers(text)
    procedures = []
    order = 0

    module_pattern = re.compile(
        r"Practicum Module:\s*(CMT\s*0?\d+)\s*([^\n]+)?\n(.*?)(?=Practicum Module:|Checklist \d+:|===== PAGE|\Z)",
        re.DOTALL | re.IGNORECASE,
    )
    for m in module_pattern.finditer(text):
        mod_code = re.sub(r"\s+", "", m.group(1).upper())
        mod_title = (m.group(2) or "").strip()
        body = m.group(3)
        dept = "patient_care"
        section = mod_code
        for marker, _title, d, sec in markers:
            mk = marker.replace(" ", "")
            if mk in mod_code or mod_code in mk:
                dept = d or dept
                section = sec or mod_code
                break
        skills_block = re.search(
            r"Competencies/Skills:?\s*(.*?)(?=Practicum Resources|Prerequisite|Clinical Placement|Practical Attachment|Activity \d|$)",
            body,
            re.DOTALL | re.IGNORECASE,
        )
        if skills_block:
            bullets = re.findall(
                r"[\uf0b7\u2022\-]\s*(.+?)(?=[\uf0b7\u2022\-]|Practicum Resources|Prerequisite|Clinical Placement|Practical Attachment|Activity \d|$)",
                skills_block.group(1),
                re.DOTALL,
            )
            for i, bullet in enumerate(bullets, 1):
                name = re.sub(r"\s+", " ", bullet).strip()
                if len(name) < 8 or len(name) > 500:
                    continue
                code = f"{mod_code}-C{i:02d}"
                procedures.append({
                    "department_code": dept,
                    "code": code,
                    "name": name[:255],
                    "description": f"{mod_code} {mod_title} · module competency",
                    "assessment_modes": DEFAULT_ASSESSMENT,
                    "practicum_section": section or mod_code,
                    "min_required_count": 1,
                    "sort_order": order,
                    "source": "module_competency",
                })
                order += 1

    checklist_header = re.compile(r"Checklist\s+(\d+)\s*:\s*(.+?)(?:\n|$)", re.IGNORECASE)
    seen_chk = set()
    for m in checklist_header.finditer(text):
        num = int(m.group(1))
        if num in seen_chk:
            continue
        seen_chk.add(num)
        title = re.sub(r"\s+", " ", m.group(2)).strip(" .")
        if len(title) < 3:
            continue
        dept = department_from_title(title, nta, num)
        code = f"CHK{num:02d}"
        procedures.append({
            "department_code": dept,
            "code": code,
            "name": title[:255],
            "description": f"Practicum guide Checklist {num}",
            "assessment_modes": "Checklist, Practical, Logbook / procedure record (instructor sign-off)",
            "practicum_section": f"Checklist {num}",
            "min_required_count": 1,
            "sort_order": order,
            "source": "checklist",
        })
        order += 1

    sub_pattern = re.compile(r"^(\d{1,2})\.(\d{1,2})(?:\.(\d{1,2}))?\s+(.+?)\s*$", re.MULTILINE)
    sub_seen = set()
    for m in sub_pattern.finditer(text):
        chk, g1, g2, desc = m.group(1), m.group(2), m.group(3), m.group(4).strip()
        if g2 is None:
            continue
        desc = re.sub(r"\s+", " ", desc)
        if len(desc) < 6 or "Standard Criteria" in desc or desc.lower() in (
            "observed",
            "not observed",
            "not applicable",
            "comments",
            "skills",
        ):
            continue
        key = f"{chk}.{g1}.{g2 or '0'}.{desc[:40]}"
        if key in sub_seen:
            continue
        sub_seen.add(key)
        num = int(chk)
        sub = f"{g1}.{g2}" + (f".{m.group(3)}" if m.group(3) else "")
        code = f"CHK{num:02d}-{sub.replace('.', '-')}"
        if len(code) > 32:
            code = code[:32]
        dept = department_from_title(desc, nta, num)
        procedures.append({
            "department_code": dept,
            "code": code,
            "name": desc[:255],
            "description": f"Checklist {num} criterion {sub}",
            "assessment_modes": "Checklist",
            "practicum_section": f"Checklist {num}",
            "min_required_count": 0,
            "sort_order": order,
            "source": "checklist_criterion",
        })
        order += 1

    used = {}
    for p in procedures:
        base = p["code"]
        if base not in used:
            used[base] = 0
            continue
        used[base] += 1
        p["code"] = f"{base[:28]}-{used[base]}"

    for p in procedures:
        m = re.match(r"^(CHK\d+)", p["code"])
        p["parent_code"] = (
            m.group(1)
            if m and p["source"] == "checklist_criterion"
            else (p["code"] if p["source"] == "checklist" else None)
        )

    out: Path = cfg["json"]
    out.write_text(
        json.dumps(
            {
                "source": cfg["source"],
                "nta_level": nta,
                "procedure_count": len(procedures),
                "procedures": procedures,
            },
            indent=2,
            ensure_ascii=False,
        ),
        encoding="utf-8",
    )

    by_source = {}
    for p in procedures:
        by_source[p["source"]] = by_source.get(p["source"], 0) + 1
    print(f"Level {nta}: wrote {len(procedures)} procedures to {out}")
    print("By source:", by_source)


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--level", choices=["4", "5", "6"], default="4")
    args = parser.parse_args()
    parse_level(args.level)


if __name__ == "__main__":
    main()
