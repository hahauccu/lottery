<?php

namespace App\Models;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PrizeWinner extends Model
{
    protected $fillable = [
        'prize_id',
        'employee_id',
        'sequence',
        'won_at',
        'claim_token',
        'claimed_at',
        'notified_at',
    ];

    protected $casts = [
        'won_at' => 'datetime',
        'claimed_at' => 'datetime',
        'notified_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (PrizeWinner $winner) {
            if (empty($winner->claim_token)) {
                $winner->claim_token = $winner->generateClaimToken();
            }
        });
    }

    public function prize(): BelongsTo
    {
        return $this->belongsTo(Prize::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function generateClaimToken(): string
    {
        return Str::random(64);
    }

    public function isClaimed(): bool
    {
        return $this->claimed_at !== null;
    }

    public function isNotified(): bool
    {
        return $this->notified_at !== null;
    }

    public function getClaimUrl(): string
    {
        return url('/claim/'.$this->claim_token);
    }

    public function generateQrCodeSvg(): string
    {
        return QrCode::format('svg')
            ->size(200)
            ->margin(1)
            ->generate($this->getClaimUrl());
    }

    public function generateQrCodeBase64(): string
    {
        $result = Builder::create()
            ->writer(new PngWriter)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->data($this->getClaimUrl())
            ->size(200)
            ->margin(4)
            ->build();

        return 'data:image/png;base64,'.base64_encode($result->getString());
    }
}
