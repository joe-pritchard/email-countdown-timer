<?php
declare(strict_types=1);
/**
 * Util.php
 *
 * @project  email-countdown-timer
 * @category JoePritchard\EmailCountdownTimer
 * @author   Joe Pritchard <joe@joe-pritchard.uk>
 *
 * Created:  17/07/19 12:43
 *
 */

namespace JoePritchard\EmailCountdownTimer;


use InvalidArgumentException;

/**
 * Class Util
 *
 * @package JoePritchard\EmailCountdownTimer
 */
class Util
{
    /**
     * Convert a hex colour to rgb
     *
     * @param  string $hex
     * @return array
     */
    public static function hex2rgb($hex)
    {
        $hex = str_replace('#', '', $hex);

        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }

        return [$r, $g, $b];
    }

    /**
     * Create a filled box to use at the base
     *
     * @param resource $image
     */
    public static function createFilledBox(&$image, int $width, int $height, array $boxColor)
    {
        if (!is_resource($image)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Argument must be a valid resource type. %s given.',
                    gettype($image)
                )
            );
        }

        $color = imagecolorallocate($image, $boxColor[0], $boxColor[1], $boxColor[2]);
        imagefilledrectangle($image, 0, 0, $width, $height, $color);
    }
}