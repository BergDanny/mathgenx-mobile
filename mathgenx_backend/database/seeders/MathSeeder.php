<?php

namespace Database\Seeders;

use App\Models\DskpCriteria;
use App\Models\MathAnswer;
use App\Models\MathQuestion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MathSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $questions = [
            [1, 'Which of the following is a negative integer?', '1.1.2'],
            [2, 'Arrange the following numbers in ascending order: -2, 3, 0, -5', '1.1.4'],
            [3, 'The temperature at midnight is -4°C. By morning, the temperature increases by 7°C. What is the new temperature?', '1.2.1'],
            [4, 'Simplify: (-6) + (-3)', '1.2.1'],
            [5, 'Simplify: (-8) x (-2)', '1.2.2'],
            [6, 'Find the value of 12 ÷ (-3)', '1.2.2'],
            [7, 'Which of the following fractions is equal to -1/2?', '1.3.2'],
            [8, 'On the number line, which point represents -0.7?', '1.4.1'],
            [9, 'Which of the following numbers can be written as a fraction of two integers?', '1.5.1'],
            [10, 'Simplify: (-4) + 6 - (-3)', '1.2.3'],
        ];

        $qMap = [];
        foreach ($questions as [$id, $text, $code]) {
            $question = MathQuestion::create([
                'question_text'     => $text,
                'criteria_id'       => DskpCriteria::where('code', $code)->first()->id ?? null,
                'skill_description' => null,
            ]);
            $qMap[$id] = $question->id;
        }

        $answers = [
            // Q1
            [1, 'A', '0', false],
            [1, 'B', '5', false],
            [1, 'C', '-3', true],
            [1, 'D', '1/2', false],

            // Q2
            [2, 'A', '-5, -2, 0, 3', true],
            [2, 'B', '-2, -5, 0, 3', false],
            [2, 'C', '3, 0, -2, -5', false],
            [2, 'D', '-5, 0, -2, 3', false],

            // Q3
            [3, 'A', '-11°C', false],
            [3, 'B', '3°C', true],
            [3, 'C', '11°C', false],
            [3, 'D', '-3°C', false],

            // Q4
            [4, 'A', '-3', false],
            [4, 'B', '-9', true],
            [4, 'C', '9', false],
            [4, 'D', '3', false],

            // Q5
            [5, 'A', '-16', false],
            [5, 'B', '16', true],
            [5, 'C', '-4', false],
            [5, 'D', '4', false],

            // Q6
            [6, 'A', '-4', true],
            [6, 'B', '4', false],
            [6, 'C', '-9', false],
            [6, 'D', '9', false],

            // Q7
            [7, 'A', '2/4', false],
            [7, 'B', '-2/4', true],
            [7, 'C', '1/-3', false],
            [7, 'D', '-3/5', false],

            // Q8
            [8, 'A', 'To the right of 0', false],
            [8, 'B', 'To the left of 0', true],
            [8, 'C', 'At point 0', false],
            [8, 'D', 'Same as +0.7', false],

            // Q9
            [9, 'A', 'π', false],
            [9, 'B', '0.25', true],
            [9, 'C', '√5', false],
            [9, 'D', '√2', false],

            // Q10
            [10, 'A', '5', true],
            [10, 'B', '8', false],
            [10, 'C', '-7', false],
            [10, 'D', '7', false],
        ];

        foreach ($answers as [$qid, $label, $text, $correct]) {
            MathAnswer::create([
                'question_id'   => $qMap[$qid],
                'option_letter' => $label,
                'answer_text'   => $text,
                'is_correct'    => $correct,
            ]);
        }
    }
}
