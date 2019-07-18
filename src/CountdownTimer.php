<?php
declare(strict_types=1);

namespace JoePritchard\EmailCountdownTimer;

use DateTime;

/**
 * Class CountdownTimer
 */
class CountdownTimer
{

    /**
     * @var object
     */
    private $base;

    /**
     * One for each second in the animation
     * @var array
     */
    private $frames = [];

    /**
     * @var array
     */
    private $delays = [];

    /**
     * @var int
     */
    private $width = 0;

    /**
     * @var int
     */
    private $height = 0;

    /**
     * @var int
     */
    private $xOffset = 0;

    /**
     * @var int
     */
    private $yOffset = 0;

    /**
     * @var int
     */
    private $delay = 100;

    /**
     * @var DateTime
     */
    private $now;

    /**
     * @var array
     */
    private $fontSettings = [];

    /**
     * @var array
     */
    private $boundingBox = [];

    /**
     * @var string
     */
    private $fontPath = __DIR__ . '/../fonts/';

    /**
     * @var int
     */
    private $seconds = 90;

    /**
     * @var int textBoxWidth
     */
    private $textBoxWidth;

    /**
     * @var int textBoxHeight
     */
    private $textBoxHeight;

    /**
     *
     * @var int loops
     */
    private $loops;

    /**
     * The datetime we are counting down to
     * @var DateTime target
     */
    private $target;

    /**
     * CountdownTimer constructor.
     *
     * @param          $settings
     *
     * @param DateTime $target
     *
     * @throws \Exception
     */
    public function __construct($settings, DateTime $target, ?string $background = null)
    {
        $font = (file_exists($settings['font']) ? $settings['font'] : $this->fontPath . $settings['font']) . '.ttf';
        if (!file_exists($font)) {
            throw new \Exception('Invalid font \'' . $font . '\'');
        }

        $this->target = $target;
        $this->now = new DateTime(date('r'));

        // character width and height will inform our offsets if we need to adjust them for a background image
        $this->fontSettings['characterWidth'] = imagefontwidth(2);

        /**
         * create new base image. If a background was specified then we'll use that as our base image
         * and it will override the width, height, and both text offsets
         */
        $this->xOffset = $settings['xOffset'];
        $this->yOffset = $settings['yOffset'];
        $this->width = $settings['width'];
        $this->height = $settings['height'];

        $this->base = $this->createBase($background, true);

        $this->boxColor = Util::hex2rgb($settings['boxColor']);
        $this->fontColor = Util::hex2rgb($settings['fontColor']);

        $this->labelOffsets = explode(',', $settings['labelOffsets']);

        $this->fontSettings['path'] = $font;
        $this->fontSettings['color'] = imagecolorallocate($this->base, $this->fontColor[0], $this->fontColor[1], $this->fontColor[2]);
        $this->fontSettings['size'] = $settings['fontSize'];

        // get the width of each character
        $string = "0:";
        $size = $this->fontSettings['size'];
        $angle = 0;
        $fontfile = $this->fontSettings['path'];

        $strlen = strlen($string);
        for ($character_index = 0; $character_index < $strlen; $character_index++) {
            $dimensions = imagettfbbox($size, $angle, $fontfile, $string[$character_index]);
            $this->fontSettings['characterWidths'][] = [
                $string[$character_index] => $dimensions[2],
            ];
        }

        // for a gif with no supplied background image, create a filled rectangle of color $this->boxColor
        if ($background === null) {
            Util::createFilledBox($this->base, $this->width, $this->height, $this->boxColor);
        }

        $this->createFrames($background);
    }

    /**
     * Generate the base image to be used for all frames
     *
     * @param string|null $background
     *
     * @param bool        $recalculate_dimensions
     *
     * @throws \Exception
     * @return false|resource
     */
    private function createBase(?string $background, $recalculate_dimensions = false)
    {
        if ($background === null) {
            $base = imagecreatetruecolor($this->width, $this->height);
            Util::createFilledBox($base, $this->width, $this->height, $this->boxColor);
        } elseif (file_exists($background)) {
            $base = imagecreatefromjpeg($background);

            // if told to do so, recalculate the image's width and height based on the background we just loaded
            if ($recalculate_dimensions) {
                $this->width = imagesx($base);
                $this->height = imagesy($base);
            }

        } else {
            throw new \Exception('Background image specified but does not exist: \'' . $background . '\'');
        }

        return $base;
    }

    /**
     * Create all of the frames for the countdown timer
     *
     * @return void
     */
    public function createFrames(?string $background)
    {
        $this->boundingBox = imagettfbbox($this->fontSettings['size'], 0, $this->fontSettings['path'], '00:00:00:00');
        $text_box_dimensions = imagettfbbox($this->fontSettings['size'], 0, $this->fontSettings['path'], '0');

        $this->textBoxWidth = $text_box_dimensions[2];
        $this->textBoxHeight = abs($text_box_dimensions[1] + $text_box_dimensions[7]);

        $this->applyTextToImage($this->base, $this->fontSettings);

        // create each frame
        for ($second = 0; $second <= $this->seconds; $second++) {
            $layer = $this->createBase($background);

            $this->applyTextToImage($layer, $this->fontSettings);
        }
    }

    /**
     * Apply each time stamp to the image
     *
     * @param resource $image
     * @param array $font
     * @param array $date
     *
     * @return void
     */
    private function applyTextToImage(&$image, array $font)
    {
        $interval = date_diff(
            $this->target,
            $this->now
        );

        if ($this->target < $this->now) {
            $text = $interval->format('00:00:00:00');
            $this->loops = 1;
        } else {
            $text = $interval->format('0%a:%H:%I:%S');
            $this->loops = 0;
        }

        $labels = ['Days', 'Hrs', 'Mins', 'Secs'];

        // apply the labels to the image $this->yOffset + ($this->textBoxHeight * 0.8)
        foreach ($labels as $key => $label) {
            imagettftext(
                $image,
                15,
                0,
                (int)($this->xOffset + ($this->textBoxWidth * $this->labelOffsets[$key])),
                98,
                $font['color'],
                $font['path'],
                $label
            );
        }

        // apply time to new image
        imagettftext($image, $font['size'], 0, $this->xOffset, $this->yOffset, $font['color'], $font['path'], $text);

        ob_start();
        imagegif($image);
        $this->frames[] = ob_get_contents();
        $this->delays[] = $this->delay;
        ob_end_clean();

        $this->now->modify('+1 second');
    }

    /**
     * showImage
     * Create the animated gif
     *
     * @return string
     */
    public function getAnimation()
    {
        return (new GIFEncoder($this->frames, $this->delays, $this->loops))->getAnimation();
    }
}
