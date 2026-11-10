<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class ScheduledReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $reportTitle;
    public array $reportResult;
    public ?string $reportUrl;
    public string $scheduleFrequencyName;
    public ?string $excelFilePath;
    public ?string $attachmentName;

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $reportTitle,
        array $reportResult,
        ?string $reportUrl = null,
        string $scheduleFrequencyName = 'Rapor Bildirimi',
        ?string $excelFilePath = null,
        ?string $attachmentName = null
    ) {
        $this->reportTitle = $reportTitle;
        $this->reportResult = $reportResult;
        $this->reportUrl = $reportUrl;
        $this->scheduleFrequencyName = $scheduleFrequencyName;
        $this->excelFilePath = $excelFilePath;
        $this->attachmentName = $attachmentName ?: ($reportTitle . '.xlsx');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '📊 [HEDEFLER] ' . $this->reportTitle . ' (' . now()->format('d.m.Y') . ')',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.scheduled_report',
            with: [
                'reportTitle' => $this->reportTitle,
                'reportResult' => $this->reportResult,
                'reportUrl' => $this->reportUrl,
                'scheduleFrequencyName' => $this->scheduleFrequencyName,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        if ($this->excelFilePath && file_exists($this->excelFilePath)) {
            return [
                Attachment::fromPath($this->excelFilePath)
                    ->as($this->attachmentName)
                    ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            ];
        }

        return [];
    }
}

