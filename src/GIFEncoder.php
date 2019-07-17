<?php
declare(strict_types=1);

namespace JoePritchard\EmailCountdownTimer;

/**
 *
 * This is a fork of https://github.com/woolm110/email-countdown-timer
 * to tody it up and make it possible to use with composer
 *
 * Encode animated gifs
 */
class GIFEncoder
{

    /**
     * The built gif image
     *
     * @var resource
     */
    private $image = '';

    /**
     * The array of images to stack
     *
     * @var array
     */
    private $buffer = [];

    /**
     * How many times to loop? 0 = infinite
     *
     * @var int
     */
    private $number_of_loops = 0;

    /**
     *
     * @var int
     */
    private $DIS = 2;

    /**
     * Which colour is transparent
     *
     * @var int
     */
    private $transparent_colour = -1;

    /**
     * Is this the first frame
     *
     * @var int
     */
    private $first_frame = true;

    /**
     * Encode an animated gif
     *
     * @param array $source_images   An array of binary source images
     * @param array $image_delays    The delays associated with the source images
     * @param int   $number_of_loops The number of times to loop
     * @param int   $transparent_colour_red
     * @param int   $transparent_colour_green
     * @param int   $transparent_colour_blue
     */
    public function __construct(array $source_images, array $image_delays, int $number_of_loops)
    {

        $this->number_of_loops = ($number_of_loops > -1) ? $number_of_loops : 0;
        $this->bufferImages($source_images);

        $this->addHeader();
        $buffer_length = count($this->buffer);
        for ($index = 0; $index < $buffer_length; $index++) {
            $this->addFrame($index, $image_delays[$index]);
        }
    }

    /**
     * Buffer the images and check to make sure they are valid
     *
     * @param array $source_images the array of source images
     *
     * @throws \Exception
     */
    private function bufferImages($source_images)
    {
        $image_count = count($source_images);
        for ($image_index = 0; $image_index < $image_count; $image_index++) {
            $this->buffer[] = $source_images[$image_index];
            if (substr($this->buffer[$image_index], 0, 6) != "GIF87a" && substr($this->buffer[$image_index], 0, 6) != "GIF89a") {
                throw new Exception('Image at position ' . $image_index . ' is not a gif');
            }

            // ?
            $magic = (13 + 3 * (2 << (ord($this->buffer[$image_index]{10}) & 0x07)));

            for ($j = $magic, $k = true; $k; $j++) {
                switch ($this->buffer[$image_index]{$j}) {
                    case "!":
                        if ((substr($this->buffer[$image_index], ($j + 3), 8)) === "NETSCAPE") {
                            throw new \Exception('You cannot make an animation from an animated gif.');
                        }
                        break;
                    case ";":
                        $k = false;
                        break;
                }
            }
        }
    }

    /**
     * Add the gif header to the image
     */
    private function addHeader()
    {
        $this->image = 'GIF89a';
        if (ord($this->buffer[0]{10}) & 0x80) {
            $cmap = 3 * (2 << (ord($this->buffer[0]{10}) & 0x07));
            $this->image .= substr($this->buffer[0], 6, 7);
            $this->image .= substr($this->buffer[0], 13, $cmap);
            $this->image .= "!\377\13NETSCAPE2.0\3\1" . $this->word($this->number_of_loops) . "\0";
        }
    }

    /**
     * Add a frame to the animation
     *
     * @param int $frame The frame to be added
     * @param int $delay The delay associated with the frame
     */
    private function addFrame(int $frame, int $delay)
    {
        $locals_str = 13 + 3 * (2 << (ord($this->buffer[$frame]{10}) & 0x07));

        $locals_end = strlen($this->buffer[$frame]) - $locals_str - 1;
        $locals_tmp = substr($this->buffer[$frame], $locals_str, $locals_end);

        $global_len = 2 << (ord($this->buffer[0]{10}) & 0x07);
        $locals_len = 2 << (ord($this->buffer[$frame]{10}) & 0x07);

        $global_rgb = substr($this->buffer[0], 13, 3 * (2 << (ord($this->buffer[0]{10}) & 0x07)));
        $locals_rgb = substr($this->buffer[$frame], 13, 3 * (2 << (ord($this->buffer[$frame]{10}) & 0x07)));

        $locals_ext = "!\xF9\x04" . chr(($this->DIS << 2) + 0) .
            chr(($delay >> 0) & 0xFF) . chr(($delay >> 8) & 0xFF) . "\x0\x0";

        if ($this->transparent_colour > -1 && ord($this->buffer[$frame]{10}) & 0x80) {
            for ($j = 0; $j < (2 << (ord($this->buffer[$frame]{10}) & 0x07)); $j++) {
                if (
                    ord($locals_rgb{3 * $j + 0}) == (($this->transparent_colour >> 16) & 0xFF) &&
                    ord($locals_rgb{3 * $j + 1}) == (($this->transparent_colour >> 8) & 0xFF) &&
                    ord($locals_rgb{3 * $j + 2}) == (($this->transparent_colour >> 0) & 0xFF)
                ) {
                    $locals_ext = "!\xF9\x04" . chr(($this->DIS << 2) + 1) .
                        chr(($delay >> 0) & 0xFF) . chr(($delay >> 8) & 0xFF) . chr($j) . "\x0";
                    break;
                }
            }
        }
        switch ($locals_tmp{0}) {
            case "!":
                $Locals_img = substr($locals_tmp, 8, 10);
                $locals_tmp = substr($locals_tmp, 18, strlen($locals_tmp) - 18);
                break;
            case ",":
                $Locals_img = substr($locals_tmp, 0, 10);
                $locals_tmp = substr($locals_tmp, 10, strlen($locals_tmp) - 10);
                break;
        }
        if (ord($this->buffer[$frame]{10}) & 0x80 && $this->first_frame === false) {
            if ($global_len == $locals_len) {
                if ($this->blockCompare($global_rgb, $locals_rgb, $global_len)) {
                    $this->image .= ($locals_ext . $Locals_img . $locals_tmp);
                } else {
                    $byte = ord($Locals_img{9});
                    $byte |= 0x80;
                    $byte &= 0xF8;
                    $byte |= (ord($this->buffer[0]{10}) & 0x07);
                    $Locals_img{9} = chr($byte);
                    $this->image .= ($locals_ext . $Locals_img . $locals_rgb . $locals_tmp);
                }
            } else {
                $byte = ord($Locals_img{9});
                $byte |= 0x80;
                $byte &= 0xF8;
                $byte |= (ord($this->buffer[$frame]{10}) & 0x07);
                $Locals_img{9} = chr($byte);
                $this->image .= ($locals_ext . $Locals_img . $locals_rgb . $locals_tmp);
            }
        } else {
            $this->image .= ($locals_ext . $Locals_img . $locals_tmp);
        }
        $this->first_frame = false;
    }

    /**
     * Compare gif blocks? What is a block?
     *
     * @param string $globalBlock
     * @param string $localBlock
     * @param int    $Len
     *
     * @return int
     */
    private function blockCompare(string $globalBlock, string $localBlock, int $Len)
    {
        for ($i = 0; $i < $Len; $i++) {
            if (
                $globalBlock{3 * $i + 0} != $localBlock{3 * $i + 0} ||
                $globalBlock{3 * $i + 1} != $localBlock{3 * $i + 1} ||
                $globalBlock{3 * $i + 2} != $localBlock{3 * $i + 2}
            ) {
                return 0;
            }
        }

        return 1;
    }

    /**
     * No clue
     *
     * @param int $int
     *
     * @return string the char you meant?
     */
    private function word(int $int)
    {
        return (chr($int & 0xFF) . chr(($int >> 8) & 0xFF));
    }

    /**
     * Return the animated gif
     *
     * @return string
     */
    public function getAnimation()
    {
        return $this->image . ';';
    }
}
