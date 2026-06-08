<?php

/**
 * Occasion Studio — calendar presets (150+ occasions).
 * Bangladeshi national & cultural, South Asian festivals, global holidays,
 * health awareness, retail promos, and industry campaigns.
 */
return [
    'masterFestivals' => array_values(array_unique([
        // —— Global new year & winter ——
        'Happy New Year', 'New Year\'s Eve', 'Makar Sankranti', 'Chinese New Year', 'Orthodox Christmas',
        'Bangabandhu Homecoming Day', 'Lohri', 'Pongal',

        // —— February & spring ——
        'Valentine\'s Day', 'International Mother Language Day', 'Spring Festival (Bosonto)',
        'Pahela Falgun', 'World Radio Day', 'Rare Disease Day', 'Hand Hygiene Day',
        'National Vitamin-A Campaign', 'World Cancer Day (4 Feb)', 'Saraswati Puja (Basant Panchami)',
        'Maha Shivratri',

        // —— March ——
        'Independence Day', 'International Women\'s Day', 'National Children\'s Day',
        'Holi Festival', 'Ramadan Kareem', 'Shab-e-Barat', 'World Water Day', 'World TB Day',
        'Doctor\'s Day Bangladesh', 'World Sleep Day', 'International Day of Happiness', 'Ugadi',

        // —— April ——
        'Pohela Boishakh', 'Eid ul-Fitr', 'Chand Raat', 'Laylat al-Qadr (Shab-e-Qadr)',
        'Jummah tul-Wida', 'Easter Sunday', 'Good Friday', 'Earth Day', 'World Health Day',
        'World Book Day', 'World Immunization Week', 'World Malaria Day', 'Autism Awareness Day',

        // —— May ——
        'Labour Day', 'Mother\'s Day', 'Buddha Purnima', 'International Nurses Day',
        'International Day of Families', 'World Hypertension Day', 'Blood Donation Day',
        'International Day of the Midwife', 'Eid Shopping Festival',

        // —— June ——
        'FIFA World Cup 2026', 'Football World Cup Fever', 'Father\'s Day', 'Eid ul-Adha',
        'World Environment Day', 'World Blood Donor Day', 'International Yoga Day',
        'World Refugee Day', 'World ORS Day', 'CSR Health Camp', 'Generic Medicine Awareness',
        'Hajj Season', 'Summer Vibes', 'Summer Mega Sale', 'Qurbani Awareness',

        // —— July ——
        'FIFA World Cup 2026', 'Monsoon Festival', 'Islamic New Year', 'Ashura (10 Muharram)',
        'Laylat al-Miraj', 'Shab-e-Meraj', 'Pharmacy Week Bangladesh', 'Agro & Fertilizer Season',
        'World Population Day', 'International Chess Day', 'World Hepatitis Day',
        'Guru Purnima', 'Cricket Victory Celebration',

        // —— August ——
        'National Mourning Day', 'Friendship Day', 'Janmashtami', 'Raksha Bandhan',
        'World Heart Day', 'Breastfeeding Week', 'Garments & Textile Expo',
        'International Youth Day', 'World Photography Day', 'Independence Day India',
        'Onam Festival', 'Breast Cancer Awareness Month',

        // —— September ——
        'Autumn Festival (Kashful)', 'World Tourism Day', 'Teacher\'s Day', 'World Teachers Day',
        'World Pharmacists Day', 'World Mental Health Day', 'International Literacy Day',
        'International Day of Peace', 'World Alzheimer\'s Day', 'Telecom 5G Launch',
        'World Suicide Prevention Day', 'National Nutrition Week',

        // —— October ——
        'Durga Puja', 'Lakshmi Puja', 'Kali Puja', 'Navratri', 'Bhai Phota (Bhai Dooj)',
        'Halloween', 'Diwali Festival', 'World Food Day', 'World Sight Day',
        'World Cancer Awareness', 'International Day of the Girl Child', 'Gandhi Jayanti',
        'World Stroke Day', 'Quality Excellence Day', 'Antibiotic Awareness Week',
        'Puja Mega Sale', 'Chhath Puja', 'Karwa Chauth',

        // —— November ——
        'Black Friday', 'Cyber Monday', 'Thanksgiving', 'Singles Day (11.11)',
        'World Diabetes Day', 'World AIDS Day', 'World Prematurity Day',
        'Milad un-Nabi (Eid-e-Milad)', 'Mawlid Celebration', 'FMCG Mega Promo',
        'Insurance Awareness Day', 'World Children\'s Day', 'International Men\'s Day',
        'Flash Sale Weekend',

        // —— December ——
        'Victory Day', 'Martyred Intellectuals Day', 'Human Rights Day',
        'International Day of Persons with Disabilities', 'Merry Christmas', 'Christmas Eve',
        'Export Summit Bangladesh', 'Banking & Finance Day', 'Digital Bangladesh Day',
        'Startup Innovation Day', 'Construction Safety Week', 'Real Estate Project Launch',
        'Winter Mega Sale', 'World Oral Health Day', 'CSR Tree Plantation Day',

        // —— Islamic campaigns ——
        'Ramadan Iftar Campaign', 'Eid Mega Sale',

        // —— Pharmaceutical & healthcare ——
        'World Kidney Day', 'World Leprosy Day', 'World Parkinson\'s Day',
        'Lung Health Awareness', 'World Mental Health Week',

        // —— Retail & campaigns ——
        'Back to School Campaign', 'Wedding Season Campaign', 'New Store Opening',
        'Brand Anniversary Sale', 'Cashback Festival', 'Grand Opening Sale',
        'Product Awareness Campaign', 'Wedding Invitation Design', 'Iftar Party Invitation',

        // —— Major industries & corporate ——
        'Corporate Anniversary', 'Employee Appreciation Day', 'FMCG Mega Promo',
        'Pharma Product Launch', 'Factory Safety Day', 'ISO Certification Celebration',

        // —— Sports & youth ——
        'National Sports Day', 'Football World Cup Fever', 'Olympic Spirit Day', 'World Athletics Day',
    ])),

    'occasionsMap' => [
        1 => [
            'Happy New Year', 'New Year\'s Eve', 'Makar Sankranti', 'Chinese New Year',
            'Orthodox Christmas', 'Bangabandhu Homecoming Day', 'Corporate Anniversary',
            'Pharma Product Launch', 'World Leprosy Day', 'Lohri', 'Pongal',
        ],
        2 => [
            'Valentine\'s Day', 'International Mother Language Day', 'Spring Festival (Bosonto)',
            'Pahela Falgun', 'World Radio Day', 'Rare Disease Day', 'Hand Hygiene Day',
            'National Vitamin-A Campaign', 'World Cancer Day (4 Feb)', 'Saraswati Puja (Basant Panchami)',
            'Maha Shivratri',
        ],
        3 => [
            'Independence Day', 'International Women\'s Day', 'National Children\'s Day',
            'Holi Festival', 'Ramadan Kareem', 'Shab-e-Barat', 'World Water Day',
            'World TB Day', 'Doctor\'s Day Bangladesh', 'World Sleep Day',
            'International Day of Happiness', 'Ugadi',
        ],
        4 => [
            'Pohela Boishakh', 'Eid ul-Fitr', 'Chand Raat', 'Laylat al-Qadr (Shab-e-Qadr)',
            'Jummah tul-Wida', 'Easter Sunday', 'Good Friday', 'Earth Day', 'World Health Day',
            'World Book Day', 'World Immunization Week', 'World Malaria Day', 'Autism Awareness Day',
        ],
        5 => [
            'Labour Day', 'Mother\'s Day', 'Buddha Purnima', 'International Nurses Day',
            'International Day of Families', 'World Hypertension Day', 'Blood Donation Day',
            'International Day of the Midwife', 'Eid Shopping Festival',
        ],
        6 => [
            'FIFA World Cup 2026', 'Football World Cup Fever', 'Father\'s Day', 'Eid ul-Adha',
            'World Environment Day', 'World Blood Donor Day', 'International Yoga Day',
            'World Refugee Day', 'World ORS Day', 'CSR Health Camp', 'Generic Medicine Awareness',
            'Hajj Season', 'Summer Vibes', 'Summer Mega Sale', 'Qurbani Awareness',
        ],
        7 => [
            'FIFA World Cup 2026', 'Monsoon Festival', 'Islamic New Year', 'Ashura (10 Muharram)',
            'Laylat al-Miraj', 'Shab-e-Meraj', 'Pharmacy Week Bangladesh', 'Agro & Fertilizer Season',
            'World Population Day', 'International Chess Day', 'World Hepatitis Day',
            'Guru Purnima', 'Cricket Victory Celebration',
        ],
        8 => [
            'National Mourning Day', 'Friendship Day', 'Janmashtami', 'Raksha Bandhan',
            'World Heart Day', 'Breastfeeding Week', 'Garments & Textile Expo',
            'International Youth Day', 'World Photography Day', 'Independence Day India',
            'Onam Festival', 'Breast Cancer Awareness Month',
        ],
        9 => [
            'Autumn Festival (Kashful)', 'World Tourism Day', 'Teacher\'s Day', 'World Teachers Day',
            'World Pharmacists Day', 'World Mental Health Day', 'International Literacy Day',
            'International Day of Peace', 'World Alzheimer\'s Day', 'Telecom 5G Launch',
            'World Suicide Prevention Day', 'National Nutrition Week',
        ],
        10 => [
            'Durga Puja', 'Lakshmi Puja', 'Kali Puja', 'Navratri', 'Bhai Phota (Bhai Dooj)',
            'Halloween', 'Diwali Festival', 'World Food Day', 'World Sight Day',
            'World Cancer Awareness', 'International Day of the Girl Child', 'Gandhi Jayanti',
            'World Stroke Day', 'Quality Excellence Day', 'Antibiotic Awareness Week',
            'Puja Mega Sale', 'Chhath Puja',
        ],
        11 => [
            'Black Friday', 'Cyber Monday', 'Thanksgiving', 'Singles Day (11.11)',
            'World Diabetes Day', 'World AIDS Day', 'World Prematurity Day',
            'Milad un-Nabi (Eid-e-Milad)', 'Mawlid Celebration', 'FMCG Mega Promo',
            'Insurance Awareness Day', 'World Children\'s Day', 'International Men\'s Day',
            'Flash Sale Weekend', 'Karwa Chauth',
        ],
        12 => [
            'Victory Day', 'Martyred Intellectuals Day', 'Human Rights Day',
            'International Day of Persons with Disabilities', 'Merry Christmas', 'Christmas Eve',
            'Export Summit Bangladesh', 'Banking & Finance Day', 'Digital Bangladesh Day',
            'Startup Innovation Day', 'Construction Safety Week', 'Real Estate Project Launch',
            'Winter Mega Sale', 'World Oral Health Day', 'CSR Tree Plantation Day',
        ],
    ],
];
