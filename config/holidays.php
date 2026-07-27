<?php

/**
 * Tanzania public holidays (fixed dates) and international observances.
 * Variable Islamic dates: set per year under islamic_dates or add via Calendar → Add event.
 * Easter: calculated automatically in HolidayCalendarService.
 */
return [
    'country' => 'TZ',
    'country_label' => 'Tanzania',

    /** month-day => title (repeats every year) */
    'fixed_public' => [
        '01-01' => 'New Year\'s Day',
        '01-12' => 'Zanzibar Revolution Day',
        '04-26' => 'Union Day',
        '05-01' => 'Labour Day',
        '07-07' => 'Saba Saba (Peasants\' Day)',
        '08-08' => 'Nane Nane (Farmers\' Day)',
        '10-14' => 'Nyerere Day',
        '12-25' => 'Christmas Day',
        '12-26' => 'Boxing Day',
    ],

    /**
     * Optional Islamic public holidays per calendar year (YYYY-MM-DD).
     * Update annually from official gazette; staff can also add events in the calendar UI.
     */
    'islamic_dates' => [
        2025 => [
            '2025-03-31' => 'Eid al-Fitr',
            '2025-04-01' => 'Eid al-Fitr (Day 2)',
            '2025-06-07' => 'Eid al-Adha',
            '2025-06-08' => 'Eid al-Adha (Day 2)',
        ],
        2026 => [
            '2026-03-20' => 'Eid al-Fitr',
            '2026-03-21' => 'Eid al-Fitr (Day 2)',
            '2026-05-27' => 'Eid al-Adha',
            '2026-05-28' => 'Eid al-Adha (Day 2)',
        ],
        2027 => [
            '2027-03-10' => 'Eid al-Fitr',
            '2027-03-11' => 'Eid al-Fitr (Day 2)',
            '2027-05-17' => 'Eid al-Adha',
            '2027-05-18' => 'Eid al-Adha (Day 2)',
        ],
    ],

    /** Short explanations shown when users click a holiday (key matches catalog key). */
    'descriptions' => [
        'fixed_01_01' => 'New Year\'s Day marks the start of the civil year. The college is normally closed; check notices for any registration or exam exceptions.',
        'fixed_01_12' => 'Zanzibar Revolution Day commemorates the 1964 revolution. It is a public holiday across the United Republic of Tanzania.',
        'fixed_04_26' => 'Union Day celebrates the union of Tanganyika and Zanzibar in 1964. No regular teaching unless the college announces otherwise.',
        'fixed_05_01' => 'International Workers\' Day (Labour Day). A public holiday honouring workers; offices and classes are usually suspended.',
        'fixed_07_07' => 'Saba Saba (Peasants\' Day) marks the founding of TANU. Historical and political significance nationally.',
        'fixed_08_08' => 'Nane Nane (Farmers\' Day) recognises the contribution of farmers to the national economy.',
        'fixed_10_14' => 'Nyerere Day honours Mwalimu Julius Kambarage Nyerere, first President of Tanzania and champion of education.',
        'fixed_12_25' => 'Christmas Day — Christian celebration of the birth of Jesus Christ. Widely observed; college closed.',
        'fixed_12_26' => 'Boxing Day — day after Christmas, public holiday in Tanzania.',
        'easter_good_friday' => 'Good Friday — Christian commemoration of the crucifixion of Jesus. Public holiday; no classes.',
        'easter_sunday' => 'Easter Sunday — central Christian feast of the Resurrection.',
        'easter_monday' => 'Easter Monday — public holiday following Easter Sunday in Tanzania.',
        'islamic_eid-al-fitr' => 'Eid al-Fitr marks the end of Ramadan (fasting month). Muslim students and staff may observe prayers; college may close.',
        'islamic_eid-al-adha' => 'Eid al-Adha (Feast of Sacrifice) is a major Islamic festival commemorating willingness to sacrifice. Public holiday when gazetted.',
    ],

    /** International / UN observances (informational on calendar) */
    'international_observances' => [
        '01-01' => 'New Year (International)',
        '02-06' => 'International Day of Zero Tolerance for Female Genital Mutilation',
        '03-08' => 'International Women\'s Day',
        '04-07' => 'World Health Day',
        '04-25' => 'World Malaria Day',
        '05-05' => 'International Day of the Midwife',
        '06-05' => 'World Environment Day',
        '06-16' => 'Day of the African Child',
        '07-30' => 'World Day against Trafficking in Persons',
        '08-12' => 'International Youth Day',
        '09-21' => 'International Day of Peace',
        '10-01' => 'International Day of Older Persons',
        '10-24' => 'United Nations Day',
        '11-20' => 'Universal Children\'s Day',
        '12-01' => 'World AIDS Day',
        '12-10' => 'Human Rights Day',
    ],
];
