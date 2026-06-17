<?php

namespace App\Services;


use Gemini\Laravel\Facades\Gemini;


class GeminiAssetService
{


    public function ask(string $question, array $context){
        $prompt = "
            You are AssetIQ Copilot.

            You are an AI assistant inside an Asset Management System.

            Answer using the company asset data below.

            Rules:

            - Be concise
            - Provide business insights
            - Mention risks
            - Suggest actions
            - If data missing say so

            ASSET DATA:

            ".json_encode($context)."


            USER QUESTION:

            ".$question;

        $response = Gemini::generativeModel(
            model:'gemini-2.0-flash'
        )
        ->generateContent($prompt);

        return $response->text();
    }


}