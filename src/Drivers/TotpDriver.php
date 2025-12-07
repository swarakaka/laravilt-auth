<?php

namespace Laravilt\Auth\Drivers;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Contracts\Auth\Authenticatable;
use Laravilt\Auth\Contracts\TwoFactorDriver;
use PragmaRX\Google2FA\Google2FA;

class TotpDriver implements TwoFactorDriver
{
    public function __construct(
        protected Google2FA $google2fa
    ) {}

    public function getName(): string
    {
        return 'totp';
    }

    public function getLabel(): string
    {
        return 'Authenticator App';
    }

    public function getIcon(): string
    {
        return 'heroicon-o-device-phone-mobile';
    }

    public function enable(Authenticatable $user): array
    {
        $secret = $this->google2fa->generateSecretKey();

        $user->two_factor_secret = encrypt($secret);
        $user->save();

        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        $qrCodeSvg = $this->generateQrCode($qrCodeUrl);

        return [
            'secret' => $secret,
            'qr_code' => $qrCodeSvg,
        ];
    }

    public function verify(Authenticatable $user, string $code): bool
    {
        if (! $user->two_factor_secret) {
            return false;
        }

        $secret = decrypt($user->two_factor_secret);

        return $this->google2fa->verifyKey($secret, $code);
    }

    public function send(Authenticatable $user): bool
    {
        // TOTP doesn't require sending
        return false;
    }

    public function requiresSending(): bool
    {
        return false;
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    /**
     * Generate QR code SVG.
     */
    protected function generateQrCode(string $url): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd
        );

        $writer = new Writer($renderer);

        return $writer->writeString($url);
    }
}
