<?php

namespace App\Services;


use Illuminate\Support\Facades\Http;
use Exception;


class XAIService
{


    public function ask(
        string $question,
        array $context
    ){


        $response = Http::withToken(

            config('services.xai.key')

        )
        ->timeout(60)
        ->post(

            config('services.xai.url'),

            [

                'model'=>config(
                    'services.xai.model'
                ),


                'messages'=>[


                    [

                    'role'=>'system',

                    'content'=>
                    "
You are AssetIQ Copilot.

You analyze asset management data.

Answer only from the supplied database context.

Be concise and business friendly.

Context:

"
.json_encode(
    $context,
    JSON_PRETTY_PRINT
)

                    ],


                    [

                    'role'=>'user',

                    'content'=>$question

                    ]

                ],


                'temperature'=>0.3

            ]

        );



        if($response->failed()){


            throw new Exception(
                $response->body()
            );

        }



        return $response->json(
            'choices.0.message.content'
        );


    }


}