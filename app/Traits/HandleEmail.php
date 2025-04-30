<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\GenericEmail;
use App\Http\Requests\SendEmailRequest;

trait HandleEmail
{
    public function sendEmail(SendEmailRequest $request): JsonResponse
    {
        try {
            $email = new GenericEmail(
                emailBody: $request->body,
                emailSubject: $request->subject,
                altBody: $request->altBody
            );

            Mail::to($request->to)
                ->cc($request->cc ?? [])
                ->bcc($request->bcc ?? [])
                ->send($email);

            Log::info('Email sent successfully', [
                'to' => $request->to,
                'subject' => $request->subject
            ]);

            return response()->json([
                'message' => 'Email sent successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send email', [
                'error' => $e->getMessage(),
                'to' => $request->to,
                'subject' => $request->subject
            ]);

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
