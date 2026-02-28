<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\VarkQuestion;
use App\Models\VarkAnswer;

class VarkSeeder extends Seeder
{
    public function run(): void
    {
        // --- Insert Questions ---
        $questions = [
            [1, 'I need to find the way to a shop that a friend has recommended. I would '],
            [2, 'A website has a video showing how to make a special graph or chart. I would learn most from '],
            [3, 'I want to find out more about a tour that I am going on. I would '],
            [4, 'When choosing a career or area of study, these are important for me '],
            [5, 'When I am learning. I '],
            [6, 'I want to save more money and to decide between a range of options. I would  '],
            [7, 'I want to learn how to play a new board game or card game. I would  '],
            [8, 'I have a problem with my heart. I would prefer that the doctor  '],
            [9, 'I want to learn to do something new on a computer. I would '],
            [10, 'When learning from the Internet, I like '],
            [11, 'I want to learn about a new project. I would ask for  '],
            [12, 'I want to learn how to take better photos. I would '],
            [13, 'I prefer a presenter or a teacher who uses  '],
            [14, 'I have finished a competition or test and I would like some feedback. I would like to have feedback '],
            [15, 'I want to find out about a house or an apartment. Before visiting it I would want  '],
            [16, 'I want to assemble a wooden table that came in parts (kitset). I would learn best from '],
        ];

        foreach ($questions as [$order, $text]) {
            VarkQuestion::create([
                'order_number'  => $order,
                'question_text' => $text,
            ]);
        }

        // --- Insert Answers ---
        $answers = [
            [1, 'a', 'find out where the shop is in relation to somewhere I know.', 'K'],
            [1, 'b', 'ask my friend to tell me the directions.', 'A'],
            [1, 'c', 'write down the street directions I need to remember.', 'R'],
            [1, 'd', 'use a map.', 'V'],

            [2, 'a', 'seeing the diagrams.', 'V'],
            [2, 'b', 'listening.', 'A'],
            [2, 'c', 'reading the words.', 'R'],
            [2, 'd', 'watching the actions.', 'K'],

            [3, 'a', 'see details about attractions and activities in the tour.', 'K'],
            [3, 'b', 'use a map and see where places are.', 'V'],
            [3, 'c', 'read about the tour in the travel brochure.', 'R'],
            [3, 'd', 'talk to the person who planned the tour or others going on the tour.', 'A'],

            [4, 'a', 'Applying my knowledge in real situations.', 'K'],
            [4, 'b', 'Communicating with others through discussion.', 'A'],
            [4, 'c', 'Working with designs, maps or charts.', 'V'],
            [4, 'd', 'Using words well in written communications.', 'R'],

            [5, 'a', 'like to talk things through.', 'A'],
            [5, 'b', 'see patterns in things.', 'V'],
            [5, 'c', 'use examples and applications.', 'K'],
            [5, 'd', 'read books, articles and handouts.', 'R'],

            [6, 'a', 'consider examples of each option using my financial information.', 'K'],
            [6, 'b', 'read a print brochure that describes the options in detail.', 'R'],
            [6, 'c', 'use graphs showing different options for different time periods.', 'V'],
            [6, 'd', 'talk with an expert about the options.', 'A'],

            [7, 'a', 'watch others play the game before joining in.', 'K'],
            [7, 'b', 'listen to somebody explaining it and ask questions.', 'A'],
            [7, 'c', 'use the diagrams that explain the various stages, moves and strategies in the game.', 'V'],
            [7, 'd', 'read the instructions.', 'R'],

            [8, 'a', 'gave me something to read to explain what was wrong.', 'R'],
            [8, 'b', 'used a plastic model to show me what was wrong.', 'K'],
            [8, 'c', 'described what was wrong.', 'A'],
            [8, 'd', 'showed me a diagram of what was wrong.', 'V'],

            [9, 'a', 'read the written instructions that came with the program.', 'R'],
            [9, 'b', 'talk with people who know about the program.', 'A'],
            [9, 'c', 'start using it and learn by trial and error.', 'K'],
            [9, 'd', 'follow the diagrams in a book.', 'V'],

            [10, 'a', 'videos showing how to do or make things.', 'K'],
            [10, 'b', 'interesting design and visual features.', 'V'],
            [10, 'c', 'interesting written descriptions, lists and explanations.', 'R'],
            [10, 'd', 'audio channels where I can listen to podcasts or interviews.', 'A'],

            [11, 'a', 'diagrams to show the project stages with charts of benefits and costs.', 'V'],
            [11, 'b', 'a written report describing the main features of the project.', 'R'],
            [11, 'c', 'an opportunity to discuss the project.', 'A'],
            [11, 'd', 'examples where the project has been used successfully.', 'K'],

            [12, 'a', 'ask questions and talk about the camera and its features.', 'A'],
            [12, 'b', 'use the written instructions about what to do.', 'R'],
            [12, 'c', 'use diagrams showing the camera and what each part does.', 'V'],
            [12, 'd', 'use examples of good and poor photos showing how to improve them.', 'K'],

            [13, 'a', 'demonstrations, models or practical sessions.', 'K'],
            [13, 'b', 'question and answer, talk, group discussion, or guest speakers.', 'A'],
            [13, 'c', 'handouts, books, or readings.', 'R'],
            [13, 'd', 'diagrams, charts, maps or graphs.', 'V'],

            [14, 'a', 'using examples from what I have done.', 'K'],
            [14, 'b', 'using a written description of my results.', 'R'],
            [14, 'c', 'from somebody who talks it through with me.', 'A'],
            [14, 'd', 'using graphs showing what I achieved.', 'V'],

            [15, 'a', 'to view a video of the property.', 'K'],
            [15, 'b', 'a discussion with the owner.', 'A'],
            [15, 'c', 'a printed description of the rooms and features.', 'R'],
            [15, 'd', 'a plan showing the rooms and a map of the area.', 'V'],

            [16, 'a', 'diagrams showing each stage of the assembly.', 'V'],
            [16, 'b', 'advice from someone who has done it before.', 'A'],
            [16, 'c', 'written instructions that came with the parts for the table.', 'R'],
            [16, 'd', 'watching a video of a person assembling a similar table.', 'K'],
        ];


        foreach ($answers as [$question_id, $letter, $text, $category]) {
            VarkAnswer::create([
                'question_id'   => $question_id,
                'option_letter' => $letter,
                'answer_text'   => $text,
                'category'      => $category,
            ]);
        }
    }
}
