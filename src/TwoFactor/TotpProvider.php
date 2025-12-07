<?php

namespace Laravilt\Auth\TwoFactor;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Contracts\Auth\Authenticatable;
use Laravilt\Auth\Contracts\TwoFactorProvider;
use PragmaRX\Google2FA\Google2FA;

class TotpProvider implements TwoFactorProvider
{
    public function __construct(
        protected Google2FA $google2fa
    ) {}

    /**
     * Get the provider name.
     */
    public function getName(): string
    {
        return 'totp';
    }

    /**
     * Generate 2FA for the user.
     *
     * @param  Authenticatable&\Illuminate\Database\Eloquent\Model  $user
     */
    public function generate(Authenticatable $user): array
    {
        $secret = $this->google2fa->generateSecretKey();

        $user->update([
            'two_factor_secret' => encrypt($secret),
            'two_factor_enabled' => true,
            'two_factor_method' => 'totp',
        ]);

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

    /**
     * Verify 2FA code.
     *
     * @param  Authenticatable&\Illuminate\Database\Eloquent\Model  $user
     */
    public function verify(Authenticatable $user, string $code): bool
    {
        if (! $user->two_factor_secret) {
            return false;
        }

        $secret = decrypt($user->two_factor_secret);

        return $this->google2fa->verifyKey($secret, $code);
    }

    /**
     * Disable 2FA for the user.
     *
     * @param  Authenticatable&\Illuminate\Database\Eloquent\Model  $user
     */
    public function disable(Authenticatable $user): bool
    {
        $user->update([
            'two_factor_secret' => null,
            'two_factor_enabled' => false,
            'two_factor_method' => null,
        ]);

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
