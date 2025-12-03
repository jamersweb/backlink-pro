<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LLMContentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LLMController extends Controller
{
    /**
     * Generate content using LLM
     */
    public function generate(Request $request)
    {
        // Validate API token
        $apiToken = $request->header('X-API-Token');
        if ($apiToken !== config('app.api_token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:comment,forum_post,bio,guest_post_pitch,anchor_text',
            'data' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $llmService = new LLMContentService();
        $type = $request->input('type');
        $data = $request->input('data');
        $tone = $data['tone'] ?? 'professional';

        try {
            switch ($type) {
                case 'comment':
                    $content = $llmService->generateComment(
                        $data['article_title'] ?? '',
                        $data['article_excerpt'] ?? '',
                        $data['target_url'] ?? '',
                        $tone
                    );
                    break;

                case 'forum_post':
                    $content = $llmService->generateForumPost(
                        $data['topic'] ?? '',
                        $data['target_url'] ?? '',
                        $tone
                    );
                    break;

                case 'bio':
                    $content = $llmService->generateBio(
                        $data['company_name'] ?? '',
                        $data['company_description'] ?? '',
                        $tone
                    );
                    break;

                case 'guest_post_pitch':
                    $content = $llmService->generateGuestPostPitch(
                        $data['blog_name'] ?? '',
                        $data['target_url'] ?? '',
                        $data['proposed_topic'] ?? '',
                        $tone
                    );
                    break;

                case 'anchor_text':
                    $variations = $llmService->generateAnchorTextVariations(
                        $data['keyword'] ?? '',
                        $data['context'] ?? '',
                        $data['count'] ?? 5
                    );
                    return response()->json([
                        'success' => true,
                        'variations' => $variations,
                    ]);

                default:
                    return response()->json(['error' => 'Invalid content type'], 400);
            }

            if ($content) {
                return response()->json([
                    'success' => true,
                    'content' => $content,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to generate content',
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
