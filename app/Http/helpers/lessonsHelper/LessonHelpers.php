<?php

namespace App\Http\helpers\lessonsHelper;

use App\Models\Lesson;

class LessonHelpers
{

    protected $request;

    public function __construct($request)
    {

        $this->request = $request;
    }

    public function UpdateOrCreate($id = null)
    {

        Lesson::updateOrCreate(
            ['id'  => $id],

            [
                'title' => $this->request['title'],

                'category' => $this->request['category'],

                'difficulty' => $this->request['difficulty'],

                'duration_minutes' => $this->request['duration_minutes'],

                'description' => $this->request['description'],

                'sign_reference' => $this->request['sign_reference'],

                'practice_tip' => $this->request['practice_tip'],
            ]
        );
    }
}
