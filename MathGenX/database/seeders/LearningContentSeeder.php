<?php

namespace Database\Seeders;

use App\Models\DskpCriteria;
use App\Models\DskpLevel;
use App\Models\DskpMain;
use App\Models\DskpMastery;
use App\Models\DskpSubtopic;
use App\Models\DskpTopic;
use App\Models\TeachingMaterial;
use App\Models\TeachingContent;
use App\Models\AssessmentMaterial;
use App\Models\AssessmentContent;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LearningContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dskps = [
            [
                'code' => 'KSSM-2015-MATH-SEC-1',
                'name' => 'KSSM Matematik Tingkatan 1',
                'subject' => 'matematik',
                'level' => 'sr1',
                'type' => 'kssm',
                'issue' => 2015,
            ],
        ];

        foreach ($dskps as $dskp) {
            DskpMain::firstOrCreate(
                [
                    'code' => $dskp['code'],
                ],
                [
                    'name' => $dskp['name'],
                    'subject' => $dskp['subject'],
                    'level' => $dskp['level'],
                    'type' => $dskp['type'],
                    'issue' => $dskp['issue'],
                ]
            );
        }


        $dskpMain = DskpMain::where('code', 'KSSM-2015-MATH-SEC-1')->firstOrFail();

        $topics = [
            [
                'code' => '1',
                'name' => 'Topik 1',
                'description' => 'Nombor Nisbah',
            ],
            [
                'code' => '2',
                'name' => 'Topik 2',
                'description' => 'Faktor dan Gandaan',
            ],
            [
                'code' => '3',
                'name' => 'Topik 3',
                'description' => 'Kuasa Dua, Punca Kuasa Dua, Kuasa Tiga dan Punca Kuasa Tiga',
            ],
            [
                'code' => '4',
                'name' => 'Topik 4',
                'description' => 'Nisbah, Kadar dan Kadaran',
            ],
            [
                'code' => '5',
                'name' => 'Topik 5',
                'description' => 'Ungkapan Algebra',
            ],
            [
                'code' => '6',
                'name' => 'Topik 6',
                'description' => 'Persamaan Linear',
            ],
            [
                'code' => '7',
                'name' => 'Topik 7',
                'description' => 'Ketaksamaan Linear',
            ],
            [
                'code' => '8',
                'name' => 'Topik 8',
                'description' => 'Garis dan Sudut',
            ],
            [
                'code' => '9',
                'name' => 'Topik 9',
                'description' => 'Poligon Asas',
            ],
            [
                'code' => '10',
                'name' => 'Topik 10',
                'description' => 'Perimeter dan Luas',
            ],
            [
                'code' => '11',
                'name' => 'Topik 11',
                'description' => 'Pengenalan Set',
            ],
            [
                'code' => '12',
                'name' => 'Topik 12',
                'description' => 'Pengendalian data',
            ],
            [
                'code' => '13',
                'name' => 'Topik 13',
                'description' => 'Teorem Pythagoras',
            ],
        ];

        foreach ($topics as $topic) {
            $dskpMain->topics()->firstOrCreate(
                [
                    'code' => $topic['code'],
                ],
                [
                    'name' => $topic['name'],
                    'description' => $topic['description'],
                ]
            );
        }

        $subtopics = [
            ['code' => '1.1', 'name' => 'Topik 1 Subtopik 1', 'description' => 'Integer', 'topic_code' => '1'],
            ['code' => '1.2', 'name' => 'Topik 1 Subtopik 2', 'description' => 'Operasi asas aritmetik yang melibatkan integer', 'topic_code' => '1'],
            ['code' => '1.3', 'name' => 'Topik 1 Subtopik 3', 'description' => 'Pecahan positif dan pecahan negatif', 'topic_code' => '1'],
            ['code' => '1.4', 'name' => 'Topik 1 Subtopik 4', 'description' => 'Perpuluhan positif dan perpuluhan negatif', 'topic_code' => '1'],
            ['code' => '1.5', 'name' => 'Topik 1 Subtopik 5', 'description' => 'Nombor nisbah', 'topic_code' => '1'],
        ];

        foreach ($subtopics as $subtopic) {
            $topic = $dskpMain->topics()->where('code', $subtopic['topic_code'])->firstOrFail();
            $topic->subtopics()->firstOrCreate(
                [
                    'code' => $subtopic['code'],
                ],
                [
                    'name' => $subtopic['name'],
                    'description' => $subtopic['description'],
                ]
            );
        }

        $dskpcriterias = [
            [
                'code' => '1.1.1',
                'name' => 'Topik 1 Subtopik 1 Kriteria 1',
                'description' => 'Mengenal nombor positif dan nombor negatif berdasarkan situasi sebenar',
                'subtopic_id' => '1.1'
            ],
            [
                'code' => '1.1.2',
                'name' => 'Topik 1 Subtopik 1 Kriteria 2',
                'description' => 'Mengenal dan memerihalkan integer',
                'subtopic_id' => '1.1'
            ],
            [
                'code' => '1.1.3',
                'name' => 'Topik 1 Subtopik 1 Kriteria 3',
                'description' => 'Mewakilkan integer pada garis nombor dan membuat perkaitan antara nilai integer dengan kedudukan integer tersebut berbanding integer lain pada garis nombor.',
                'subtopic_id' => '1.1'
            ],
            [
                'code' => '1.1.4',
                'name' => 'Topik 1 Subtopik 1 Kriteria 4',
                'description' => 'Membanding dan menyusun integer mengikut tertib.',
                'subtopic_id' => '1.1'
            ],
            [
                'code' => '1.2.1',
                'name' => 'Topik 1 Subtopik 2 Kriteria 1',
                'description' => 'Menambah dan menolak integer menggunakan garis nombor atau kaedah lain yang sesuai. Seterusnya membuat generalisasi tentang penambahan dan penolakan integer.',
                'subtopic_id' => '1.2'
            ],
            [
                'code' => '1.2.2',
                'name' => 'Topik 1 Subtopik 2 Kriteria 2',
                'description' => 'Mendarab dan membahagi integer menggunakan pelbagai kaedah. Seterusnya membuat generalisasi tentang pendaraban dan pembahagian integer.',
                'subtopic_id' => '1.2'
            ],
            [
                'code' => '1.2.3',
                'name' => 'Topik 1 Subtopik 2 Kriteria 3',
                'description' => 'Membuat pengiraan yang melibatkan gabungan operasi asas aritmetik bagi integer mengikut tertib operasi.',
                'subtopic_id' => '1.2'
            ],
            [
                'code' => '1.2.4',
                'name' => 'Topik 1 Subtopik 2 Kriteria 4',
                'description' => 'Menghuraikan hukum operasi aritmetik iaitu Hukum Identiti, Hukum Kalis Tukar Tertib, Hukum Kalis Sekutuan dan Hukum Kalis Agihan.',
                'subtopic_id' => '1.2'
            ],
            [
                'code' => '1.2.5',
                'name' => 'Topik 1 Subtopik 2 Kriteria 5',
                'description' => 'Membuat pengiraan yang efisien dengan menggunakan hukum operasi asas aritmetik.',
                'subtopic_id' => '1.2'
            ],
            [
                'code' => '1.2.6',
                'name' => 'Topik 1 Subtopik 2 Kriteria 6',
                'description' => 'Menyelesaikan masalah yang melibatkan integer.',
                'subtopic_id' => '1.2'
            ],
            [
                'code' => '1.3.1',
                'name' => 'Topik 1 Subtopik 3 Kriteria 1',
                'description' => 'Mewakilkan pecahan positif dan pecahan negatif pada garis nombor.',
                'subtopic_id' => '1.3'
            ],
            [
                'code' => '1.3.2',
                'name' => 'Topik 1 Subtopik 3 Kriteria 2',
                'description' => 'Membanding dan menyusun pecahan positif dan pecahan negatif mengikut tertib.',
                'subtopic_id' => '1.3'
            ],
            [
                'code' => '1.3.3',
                'name' => 'Topik 1 Subtopik 3 Kriteria 3',
                'description' => 'Membuat pengiraan yang melibatkan gabungan operasi asas aritmetik bagi pecahan positif dan pecahan negatif mengikut tertib operasi.',
                'subtopic_id' => '1.3'
            ],
            [
                'code' => '1.3.4',
                'name' => 'Topik 1 Subtopik 3 Kriteria 4',
                'description' => 'Menyelesaikan masalah yang melibatkan pecahan positif dan pecahan negatif.',
                'subtopic_id' => '1.3'
            ],
            [
                'code' => '1.4.1',
                'name' => 'Topik 1 Subtopik 4 Kriteria 1',
                'description' => 'Mewakilkan perpuluhan positif dan perpuluhan negatif pada garis nombor.',
                'subtopic_id' => '1.4'
            ],
            [
                'code' => '1.4.2',
                'name' => 'Topik 1 Subtopik 4 Kriteria 2',
                'description' => 'Membanding dan menyusun perpuluhan positif dan perpuluhan negatif mengikut tertib.',
                'subtopic_id' => '1.4'
            ],
            [
                'code' => '1.4.3',
                'name' => 'Topik 1 Subtopik 4 Kriteria 3',
                'description' => 'Membuat pengiraan yang melibatkan gabungan operasi asas aritmetik bagi perpuluhan positif dan perpuluhan negatif mengikut tertib operasi.',
                'subtopic_id' => '1.4'
            ],
            [
                'code' => '1.4.4',
                'name' => 'Topik 1 Subtopik 4 Kriteria 4',
                'description' => 'Menyelesaikan masalah yang melibatkan perpuluhan positif dan perpuluhan negatif',
                'subtopic_id' => '1.4'
            ],
            [
                'code' => '1.5.1',
                'name' => 'Topik 1 Subtopik 5 Kriteria 1',
                'description' => 'Mengenal dan memerihalkan nombor nisbah.',
                'subtopic_id' => '1.5'
            ],
            [
                'code' => '1.5.2',
                'name' => 'Topik 1 Subtopik 5 Kriteria 2',
                'description' => 'Membuat pengiraan yang melibatkan gabungan operasi asas aritmetik bagi nombor nisbah mengikut tertib operasi.',
                'subtopic_id' => '1.5'
            ],
            [
                'code' => '1.5.3',
                'name' => 'Topik 1 Subtopik 5 Kriteria 3',
                'description' => 'Menyelesaikan masalah yang melibatkan nombor nisbah.',
                'subtopic_id' => '1.5'
            ],
        ];

        foreach ($dskpcriterias as $criteria) {
            DskpCriteria::firstOrCreate([
                'code' => $criteria['code']
            ], [
                'name' => $criteria['name'],
                'description' => $criteria['description'],
                'subtopic_id' => DskpSubtopic::where('code', $criteria['subtopic_id'])->firstOrFail()->id
            ]);
        }

        $mastery = [
            [
                'code' => 'TP1',
                'name' => 'Tahap Penguasaan 1 ',
                'description' => 'Mempamerkan pengetahuan asas tentang integer, pecahan dan perpuluhan.',
                'topic_id' => '1',
            ],
            [
                'code' => 'TP2',
                'name' => 'Tahap Penguasaan 2',
                'description' => 'Mempamerkan kefahaman tentang nombor nisbah.',
                'topic_id' => '1',
            ],
            [
                'code' => 'TP3',
                'name' => 'Tahap Penguasaan 3',
                'description' => 'Mengaplikasikan kefahaman tentang nombor nisbah untuk melaksanakan operasi asas dan gabungan operasi asas aritmetik.',
                'topic_id' => '1',
            ],
            [
                'code' => 'TP4',
                'name' => 'Tahap Penguasaan 4',
                'description' => 'Mengaplikasikan pengetahuan dan kemahiran yang sesuai tentang nombor nisbah dalam konteks penyelesaian masalah rutin yang mudah.',
                'topic_id' => '1',
            ],
            [
                'code' => 'TP5',
                'name' => 'Tahap Penguasaan 5',
                'description' => 'Mengaplikasikan pengetahuan dan kemahiran yang sesuai tentang nombor nisbah dalam konteks penyelesaian masalah rutin yang kompleks.',
                'topic_id' => '1',
            ],
            [
                'code' => 'TP6',
                'name' => 'Tahap Penguasaan 6',
                'description' => 'Mengaplikasikan pengetahuan dan kemahiran yang sesuai tentang nombor nisbah dalam konteks penyelesaian masalah bukan rutin.',
                'topic_id' => '1',
            ],
        ];

        foreach ($mastery as $m) {
            DskpMastery::firstOrCreate([
                'code' => $m['code']
            ], [
                'name' => $m['name'],
                'description' => $m['description'],
                'topic_id' => DskpTopic::where('code', $m['topic_id'])->firstOrFail()->id,
            ]);
        }

        $teaching_material = [
            [
                'code' => 'MTT1-KSSM-1',
                'name' => 'Buku Teks Matematik Tingkatan 1 KSSM',
                'issuer' => 'KPM',
            ]
        ];

        foreach ($teaching_material as $tm) {
            TeachingMaterial::create([
                'code' => $tm['code'],
                'name' => $tm['name'],
                'issuer' => $tm['issuer'],
            ]);
        }

        $teaching_contents = [
            [
                'code' => '1',
                'page' => '2',
                'concept' => 'Mengenal nombor positif dan nombor negatif berdasarkan situasi sebenar',
                'example' => 'Lif naik dua tingkat = +2, lif turun satu tingkat = -1',
                'question_number' => 'Q1.1a-1',
                'question_text' => 'Wakilkan suhu 45°C dan -10°C sebagai integer',
                'calculation_step' => 'Langkah 1: Kenal pasti integer → +45, -10',
                'answer' => '=+45, -10',
                'teaching_id' => '1',
                'criteria_id' => '1.1.1',
            ],
            [
                'code' => '2',
                'page' => '2',
                'concept' => 'Mengenal nombor positif dan nombor negatif berdasarkan situasi sebenar',
                'example' => 'Kereta A bergerak 40 m ke kanan = +40, Kereta B bergerak 50 m ke kiri = -50',
                'question_number' => 'Q1.1a-2',
                'question_text' => 'Wakilkan pergerakan kereta A dan kereta B dengan integer',
                'calculation_step' => 'Langkah 1: Kenal pasti arah → +40, -50',
                'answer' => '=+40, -50',
                'teaching_id' => '1',
                'criteria_id' => '1.1.1',
            ],
        ];

        foreach ($teaching_contents as $content) {
            TeachingContent::create([
                'code' => $content['code'],
                'page' => $content['page'],
                'concept' => $content['concept'],
                'example' => $content['example'],
                'question_number' => $content['question_number'],
                'question_text' => $content['question_text'],
                'calculation_step' => $content['calculation_step'],
                'answer' => $content['answer'],
                'teaching_id' => TeachingMaterial::where('code', 'MTT1-KSSM-1')->firstOrFail()->id,
                'criteria_id' => DskpCriteria::where('code', str_replace('-', '.', $content['criteria_id']))->first()->id ?? dd('Criteria not found: ' . $content['criteria_id']),
            ]);
        }

        $assessment_material = [
            [
                'code' => 'GPT1-KSSM-1',
                'name' => 'Soalan Tingkatan 1 Generative',
                'issuer' => 'GPT',
            ]
        ];

        foreach ($assessment_material as $asm) {
            AssessmentMaterial::create([
                'code' => $asm['code'],
                'name' => $asm['name'],
                'issuer' => $asm['issuer'],
            ]);
        }

        $assessment_content = [
            [
                'code' => '1',
                'concept' => 'Mengenal nombor positif dan nombor negatif berdasarkan situasi sebenar',
                'example' => 'Suhu -5°C dan +7°C',
                'question_number' => 'Q2001',
                'question_text' => 'Nyatakan nombor yang lebih besar antara -5 dan +7.',
                'calculation_step' => 'Bandingkan kedudukan pada garis nombor, -5 di kiri sifar, +7 di kanan sifar, maka +7 lebih besar.',
                'answer' => '7',
                'teaching_id' => '1',
                'criteria_id' => '1.1.1',
                'learning_type' => 'V, R', 
                'mastery_id' => 'TP2', 
            ],
            [
                'code' => '2',
                'concept' => 'Mengenal nombor positif dan nombor negatif berdasarkan situasi sebenar',
                'example' => 'Paras air +3 m, -2 m',
                'question_number' => 'Q2002',
                'question_text' => 'Antara +3 m dan -2 m, manakah lebih tinggi paras airnya?',
                'calculation_step' => '3 m berada di atas sifar, -2 m berada di bawah sifar, maka +3 m lebih tinggi.',
                'answer' => '3 m',
                'teaching_id' => '1',
                'criteria_id' => '1.1.1',
                'learning_type' => 'V, R',
                'mastery_id' => 'TP2',
            ],
            [
                'code' => '3',
                'concept' => 'Mengenal nombor positif dan nombor negatif berdasarkan situasi sebenar',
                'example' => 'Suhu -8°C, +4°C',
                'question_number' => 'Q2003',
                'question_text' => 'Suhu manakah lebih sejuk, -8°C atau +4°C?',
                'calculation_step' => 'Suhu negatif lebih rendah, -8°C lebih sejuk berbanding +4°C.',
                'answer' => '-8°C',
                'teaching_id' => '1',
                'criteria_id' => '1.1.1',
                'learning_type' => 'V, R',
                'mastery_id' => 'TP2',
            ],
            [
                'code' => '4',
                'concept' => 'Mengenal nombor positif dan nombor negatif berdasarkan situasi sebenar',
                'example' => 'Kedalaman laut -15 m, -5 m',
                'question_number' => 'Q2004',
                'question_text' => 'Antara -15 m dan -5 m, yang manakah lebih dalam?',
                'calculation_step' => 'Nilai mutlak lebih besar bermaksud lebih dalam, jadi -15 m lebih dalam.',
                'answer' => '-15 m',
                'teaching_id' => '1',
                'criteria_id' => '1.1.1',
                'learning_type' => 'V, R',
                'mastery_id' => 'TP2',
            ],
            [
                'code' => '5',
                'concept' => 'Mengenal nombor positif dan nombor negatif berdasarkan situasi sebenar',
                'example' => 'Untung +120, Rugi -80',
                'question_number' => 'Q2005',
                'question_text' => 'Seorang peniaga untung +120 manakala seorang lagi rugi -80. Siapa lebih baik kedudukannya?',
                'calculation_step' => 'Untung adalah positif, rugi negatif, maka +120 lebih baik.',
                'answer' => 'Peniaga dengan +120',
                'teaching_id' => '1',
                'criteria_id' => '1.1.1',
                'learning_type' => 'V, R',
                'mastery_id' => 'TP2',
            ],
            [
                'code' => '6',
                'concept' => 'Mengenal nombor positif dan nombor negatif berdasarkan situasi sebenar',
                'example' => 'Suhu -12°C, -3°C',
                'question_number' => 'Q2006',
                'question_text' => 'Antara -12°C dan -3°C, manakah lebih hampir kepada sifar?',
                'calculation_step' => '-3°C lebih dekat dengan sifar berbanding -12°C.',
                'answer' => '-3°C',
                'teaching_id' => '1',
                'criteria_id' => '1.1.1',
                'learning_type' => 'V, R',
                'mastery_id' => 'TP2',
            ],
            [
                'code' => '7',
                'concept' => 'Mengenal nombor positif dan nombor negatif berdasarkan situasi sebenar',
                'example' => 'Markah +15, -10',
                'question_number' => 'Q2007',
                'question_text' => 'Ahmad mendapat +15 markah, Ali -10 markah. Siapa skor lebih tinggi?',
                'calculation_step' => '15 lebih besar daripada -10.',
                'answer' => 'Ahmad',
                'teaching_id' => '1',
                'criteria_id' => '1.1.1',
                'learning_type' => 'V, R',
                'mastery_id' => 'TP2',
            ],
            [
                'code' => '8',
                'concept' => 'Mengenal nombor positif dan nombor negatif berdasarkan situasi sebenar',
                'example' => 'Kedudukan lif tingkat +5, -2',
                'question_number' => 'Q2008',
                'question_text' => 'Lif A berada di tingkat +5 dan lif B di tingkat -2. Lif manakah lebih tinggi?',
                'calculation_step' => '5 di atas sifar, -2 di bawah sifar, maka lif A lebih tinggi.',
                'answer' => 'Lif A',
                'teaching_id' => '1',
                'criteria_id' => '1.1.1',
                'learning_type' => 'V, R',
                'mastery_id' => 'TP2',
            ],
            [
                'code' => '9',
                'concept' => 'Mengenal nombor positif dan nombor negatif berdasarkan situasi sebenar',
                'example' => 'Suhu -1°C, -9°C',
                'question_number' => 'Q2009',
                'question_text' => 'Antara -1°C dan -9°C, manakah lebih panas?',
                'calculation_step' => '-1°C lebih dekat sifar, jadi lebih panas.',
                'answer' => '-1°C',
                'teaching_id' => '1',
                'criteria_id' => '1.1.1',
                'learning_type' => 'V, R',
                'mastery_id' => 'TP2',
            ],
            [
                'code' => '10',
                'concept' => 'Mengenal nombor positif dan nombor negatif berdasarkan situasi sebenar',
                'example' => 'Paras sungai +2 m, -4 m',
                'question_number' => 'Q2010',
                'question_text' => 'Paras sungai A +2 m, sungai B -4 m. Sungai manakah lebih tinggi?',
                'calculation_step' => '2 lebih besar daripada -4.',
                'answer' => 'Sungai A',
                'teaching_id' => '1',
                'criteria_id' => '1.1.1',
                'learning_type' => 'V, R',
                'mastery_id' => 'TP2',
            ],
            [
                'code' => '11',
                'concept' => 'Mengenal nombor positif dan nombor negatif berdasarkan situasi sebenar',
                'example' => 'Skor perlawanan -6, +9',
                'question_number' => 'Q2011',
                'question_text' => 'Antara skor -6 dan +9, yang manakah lebih tinggi?',
                'calculation_step' => 'Positif sentiasa lebih tinggi daripada negatif.',
                'answer' => '9',
                'teaching_id' => '1',
                'criteria_id' => '1.1.1',
                'learning_type' => 'V, R',
                'mastery_id' => 'TP2',
            ],
            [
                'code' => '12',
                'concept' => 'Mengenal nombor positif dan nombor negatif berdasarkan situasi sebenar',
                'example' => 'Ketinggian -20 m, -8 m',
                'question_number' => 'Q2012',
                'question_text' => 'Antara -20 m dan -8 m, yang manakah lebih rendah?',
                'calculation_step' => '-20 m lebih jauh dari sifar, jadi lebih rendah.',
                'answer' => '-20 m',
                'teaching_id' => '1',
                'criteria_id' => '1.1.1',
                'learning_type' => 'V, R',
                'mastery_id' => 'TP2',
            ],
            [
                'code' => '13',
                'concept' => 'Mengenal nombor positif dan nombor negatif berdasarkan situasi sebenar',
                'example' => 'Suhu +11°C, -11°C',
                'question_number' => 'Q2013',
                'question_text' => 'Antara +11°C dan -11°C, manakah lebih panas?',
                'calculation_step' => '11°C lebih besar dan lebih panas.',
                'answer' => '11°C',
                'teaching_id' => '1',
                'criteria_id' => '1.1.1',
                'learning_type' => 'V, R',
                'mastery_id' => 'TP2',
            ],
            [
                'code' => '14',
                'concept' => 'Mengenal nombor positif dan nombor negatif berdasarkan situasi sebenar',
                'example' => 'Akaun bank +250, -100',
                'question_number' => 'Q2014',
                'question_text' => 'Baki akaun A +250 dan akaun B -100. Akaun siapa lebih baik?',
                'calculation_step' => 'Positif lebih baik daripada negatif.',
                'answer' => 'Akaun A',
                'teaching_id' => '1',
                'criteria_id' => '1.1.1',
                'learning_type' => 'V, R',
                'mastery_id' => 'TP2',
            ],
            [
                'code' => '15',
                'concept' => 'Mengenal nombor positif dan nombor negatif berdasarkan situasi sebenar',
                'example' => 'Kedudukan lif -7, -3',
                'question_number' => 'Q2015',
                'question_text' => 'Lif A di -7 dan lif B di -3. Lif manakah lebih dekat dengan aras tanah (0)?',
                'calculation_step' => '-3 lebih dekat sifar berbanding -7.',
                'answer' => 'Lif B',
                'teaching_id' => '1',
                'criteria_id' => '1.1.1',
                'learning_type' => 'V, R',
                'mastery_id' => 'TP2',
            ],
            [
                'code' => '16',
                'concept' => 'Mengenal nombor positif dan nombor negatif berdasarkan situasi sebenar',
                'example' => 'Suhu -2°C, 0°C',
                'question_number' => 'Q2016',
                'question_text' => 'Antara -2°C dan 0°C, yang manakah lebih tinggi?',
                'calculation_step' => '0 lebih besar daripada -2.',
                'answer' => '0°C',
                'teaching_id' => '1',
                'criteria_id' => '1.1.1',
                'learning_type' => 'V, R',
                'mastery_id' => 'TP2',
            ],
            [
                'code' => '17',
                'concept' => 'Mengenal nombor positif dan nombor negatif berdasarkan situasi sebenar',
                'example' => 'Kedudukan +6, -6',
                'question_number' => 'Q2017',
                'question_text' => 'Antara +6 dan -6, nombor manakah lebih besar?',
                'calculation_step' => '6 lebih besar berbanding -6.',
                'answer' => '6',
                'teaching_id' => '1',
                'criteria_id' => '1.1.1',
                'learning_type' => 'V, R',
                'mastery_id' => 'TP2',
            ],
            [
                'code' => '18',
                'concept' => 'Mengenal nombor positif dan nombor negatif berdasarkan situasi sebenar',
                'example' => 'Paras laut -50 m, +10 m',
                'question_number' => 'Q2018',
                'question_text' => 'Antara -50 m dan +10 m, manakah lebih tinggi?',
                'calculation_step' => '10 lebih tinggi berbanding -50.',
                'answer' => '10 m',
                'teaching_id' => '1',
                'criteria_id' => '1.1.1',
                'learning_type' => 'V, R',
                'mastery_id' => 'TP2',
            ],
            [
                'code' => '19',
                'concept' => 'Mengenal nombor positif dan nombor negatif berdasarkan situasi sebenar',
                'example' => 'Skor ujian -2, +2',
                'question_number' => 'Q2019',
                'question_text' => 'Seorang pelajar mendapat -2 markah, seorang lagi +2 markah. Siapa lebih baik?',
                'calculation_step' => '2 lebih besar daripada -2.',
                'answer' => 'Pelajar dengan +2',
                'teaching_id' => '1',
                'criteria_id' => '1.1.1',
                'learning_type' => 'V, R',
                'mastery_id' => 'TP2',
            ],
            [
                'code' => '20',
                'concept' => 'Mengenal nombor positif dan nombor negatif berdasarkan situasi sebenar',
                'example' => 'Suhu -15°C, -5°C',
                'question_number' => 'Q2020',
                'question_text' => 'Antara -15°C dan -5°C, manakah lebih panas?',
                'calculation_step' => '-5 lebih dekat dengan sifar, maka lebih panas.',
                'answer' => '-5°C',
                'teaching_id' => '1',
                'criteria_id' => '1.1.1',
                'learning_type' => 'V, R',
                'mastery_id' => 'TP2',
            ],
        ];

        foreach ($assessment_content as $asc) {
            AssessmentContent::create([
                'code' => $asc['code'],
                'concept' => $asc['concept'],
                'example' => $asc['example'],
                'question_number' => $asc['question_number'],
                'question_text' => $asc['question_text'],
                'calculation_step' => $asc['calculation_step'],
                'answer' => $asc['answer'],
                'learning_type' => $asc['learning_type'],
                'assessment_id' => AssessmentMaterial::where('code', 'GPT1-KSSM-1')->firstOrFail()->id,
                'criteria_id' => DskpCriteria::where('code', str_replace('-', '.', $asc['criteria_id']))->first()->id ?? dd('Criteria not found: ' . $asc['criteria_id']),
                'mastery_id' => DskpMastery::where('code', $asc['mastery_id'])->firstOrFail()->id,
            ]);
        }
    }
}
