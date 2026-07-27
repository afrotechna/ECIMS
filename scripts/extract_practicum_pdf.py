# -*- coding: utf-8 -*-
"""Extract text from CMT practicum PDF for parsing."""
import argparse
from pathlib import Path

from pypdf import PdfReader

BASE = Path(__file__).resolve().parents[1]
GUIDES = BASE / "storage" / "app" / "public" / "clinical-guides"

LEVELS = {
    "4": {
        "pdf": "CMT 4 TUTORS  PRACTICUM GUIDE FINAL.pdf",
        "out": BASE / "storage" / "app" / "clinical-guide-l4-full-text.txt",
    },
    "5": {
        "pdf": "PRACTICUM GUIDE NTA L5_Tutors.pdf",
        "out": BASE / "storage" / "app" / "clinical-guide-l5-full-text.txt",
    },
    "6": {
        "pdf": "NTA LEVEL 6 PG FOR TUTORS REVIEWED OCTOBER 2022- (1).pdf",
        "out": BASE / "storage" / "app" / "clinical-guide-l6-full-text.txt",
    },
}


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--level", choices=["4", "5", "6"], default="4")
    args = parser.parse_args()
    cfg = LEVELS[args.level]
    path = GUIDES / cfg["pdf"]
    if not path.is_file():
        raise SystemExit(f"PDF not found: {path}")

    r = PdfReader(str(path))
    with cfg["out"].open("w", encoding="utf-8", errors="replace") as f:
        for i, page in enumerate(r.pages):
            t = page.extract_text() or ""
            f.write(f"\n\n===== PAGE {i + 1} =====\n\n")
            f.write(t)

    print(f"Level {args.level}: wrote {len(r.pages)} pages to {cfg['out']}")


if __name__ == "__main__":
    main()
