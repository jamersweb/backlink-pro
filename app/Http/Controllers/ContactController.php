<?php

namespace App\Http\Controllers;

use App\Models\ContactLead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class ContactController extends Controller
{
    /**
     * Show contact page
     */
    public function index()
    {
        return Inertia::render('Contact/Index');
    }

    /**
     * Store contact form submission
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        // Store lead in database
        $lead = ContactLead::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'subject' => $validated['subject'] ?? 'General Inquiry',
            'message' => $validated['message'],
            'status' => ContactLead::STATUS_NEW,
        ]);

        // Optionally send email notification
        try {
            $subject = $validated['subject'] ?? 'General Inquiry';
            Mail::raw(
                "New Contact Form Submission\n\n" .
                "Name: {$validated['name']}\n" .
                "Email: {$validated['email']}\n" .
                "Subject: {$subject}\n\n" .
                "Message:\n{$validated['message']}",
                function ($message) use ($subject) {
                    $message->to(config('mail.from.address'))
                        ->subject('New Contact Form Submission: ' . $subject);
                }
            );
        } catch (\Exception $e) {
            // Log error but don't fail the request
            \Log::error('Failed to send contact email: ' . $e->getMessage());
        }

        return back()->with('success', 'Thank you for your message! We\'ll get back to you soon.');
    }
}
