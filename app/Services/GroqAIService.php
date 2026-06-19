<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Services\AssetActionService; // executes the actual DB ops

class GroqAIService
{
    public function __construct(
        private AssetActionService $actions
    ) {}

    public function ask(string $question, array $context, int $companyId)
    {
        $messages = [
            ["role" => "system", "content" => $this->buildPrompt($context)],
            ["role" => "user", "content" => $question],
        ];

        // Loop to handle multi-step tool calls
        for ($i = 0; $i < 5; $i++) {
            $response = $this->callGroq($messages);

            $choice = $response['choices'][0]['message'];

            // No tool call -> final answer
            if (empty($choice['tool_calls'])) {
                return $choice['content'];
            }

            $messages[] = $choice; // assistant's tool_calls message

            foreach ($choice['tool_calls'] as $toolCall) {
                $name = $toolCall['function']['name'];
                $args = json_decode($toolCall['function']['arguments'], true);

                $result = $this->actions->execute($name, $args, $companyId);

                $messages[] = [
                    "role" => "tool",
                    "tool_call_id" => $toolCall['id'],
                    "content" => json_encode($result),
                ];
            }
        }

        return "I wasn't able to complete that action — please try rephrasing.";
    }

    private function callGroq(array $messages): array
    {
        $response = Http::withToken(config('services.groq.key'))
            ->timeout(60)
            ->post(config('services.groq.url'), [
                "model" => config('services.groq.model'),
                "messages" => $messages,
                "tools" => $this->getToolDefinitions(),
                "tool_choice" => "auto",
                "temperature" => 0.3,
                "max_tokens" => 2000,
            ]);

        if (!$response->successful()) {
            throw new \Exception($response->body());
        }

        return $response->json();
    }

    private function buildPrompt(array $context): string
    {
        return <<<PROMPT
            You are AssetIQ Copilot — an embedded AI assistant inside the AssetIQ enterprise asset management platform, speaking to an operations or finance manager.

            ROLE
            - You help manage the full asset lifecycle: acquisition, assignment, maintenance, depreciation, and disposal.
            - You can both ANSWER questions from the company's live asset data below, and PERFORM actions (create, update, search, decommission) using the tools provided.

            DATA SCOPE
            - Use ONLY the Company Asset Context provided below. Never invent asset IDs, costs, vendors, or statuses.
            - If the answer isn't in the data, say so plainly and offer to search or create the record instead.

            Company Asset Context:
            {$this->jsonContext($context)}

            RESPONSE STYLE (premium, intuitive)
            - Lead with the direct answer in 1 sentence, then supporting detail.
            - Use short bold headers and bullet points for anything with more than 2 items — never wall-of-text.
            - Reference assets by name AND ID (e.g. "Dell Latitude 5420 (AST-0231)") so the user can act on it.
            - Surface cost figures in KES with thousands separators.
            - When relevant, proactively flag risk: overdue maintenance, assets nearing end-of-life, underutilized assets, or vendor concentration risk — even if not explicitly asked.
            - Keep tone confident and consultative, like a sharp ops analyst — not robotic, not overly casual.

            ACTIONS & TOOLS
            - Use a tool whenever the user's intent is to create, update, find, or remove a record — don't just describe what you'd do, call the tool.
            - For create_asset/update_asset: confirm back with the asset name/ID and the fields you set, never just "done."
            - For delete_asset: NEVER call it with confirmed=true unless the user has explicitly said yes/confirm/proceed in this conversation. If they haven't, ask once, clearly, stating which asset and that this will decommission it.
            - For search_assets: if the result set is large, summarize counts and patterns before listing examples — don't dump raw rows.

            GUARDRAILS
            - Never expose data, asset IDs, or vendor names belonging to another company.
            - Never fabricate a tool result — only report what a tool actually returned.
            PROMPT;
            }

            private function jsonContext(array $context): string
            {
                return json_encode($context, JSON_PRETTY_PRINT);
    }

    private function getToolDefinitions(): array
    {
        return [
            [
                "type" => "function",
                "function" => [
                    "name" => "create_asset",
                    "description" => "Create a new asset record for the company",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                            "name" => ["type" => "string"],
                            "description" => ["type" => "string"],
                            "category_id" => ["type" => "integer"],
                            "vendor_id" => ["type" => "integer", "description" => "Optional vendor to link"],
                            "serial_number" => ["type" => "string"],
                            "purchase_cost" => ["type" => "number"],
                            "acquisition_date" => ["type" => "string", "description" => "YYYY-MM-DD"],
                            "location" => ["type" => "string"],
                            "responsible_person" => ["type" => "string"],
                            "status" => ["type" => "string", "enum" => ["active", "under_maintenance", "inactive"]],
                            "useful_life_years" => ["type" => "integer"],
                        ],
                        "required" => ["name", "category_id"]
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "update_asset",
                    "description" => "Update fields on an existing asset by ID",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                            "asset_id" => ["type" => "integer"],
                            "fields" => [
                                "type" => "object",
                                "description" => "Allowed keys: name, description, category_id, serial_number, location, responsible_person, status, lifecycle_status, purchase_cost, warranty_start_date, warranty_end_date, useful_life_years"
                            ]
                        ],
                        "required" => ["asset_id", "fields"]
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "search_assets",
                    "description" => "Search/filter the company's assets",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                            "status" => ["type" => "string"],
                            "lifecycle_status" => ["type" => "string", "enum" => ["in_use", "in_storage", "retired", "disposed"]],
                            "category_id" => ["type" => "integer"],
                            "location" => ["type" => "string"],
                            "due_for_maintenance" => ["type" => "boolean"],
                        ]
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "delete_asset",
                    "description" => "Retire/decommission an asset. Requires explicit user confirmation before confirmed=true.",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                            "asset_id" => ["type" => "integer"],
                            "reason" => ["type" => "string"],
                            "confirmed" => ["type" => "boolean"]
                        ],
                        "required" => ["asset_id", "confirmed"]
                    ]
                ]
            ],
        ];
    }
}