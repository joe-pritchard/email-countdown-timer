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
     * @var object
     */
    private $box;

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
     * @var array
     */
    private $frames = [];

    /**
     * @var array
     */
    private $delays = [];

    /**
     * @var array
     */
    private $date = [];

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
     * CountdownTimer constructor.
     *
     * @param $settings
     *
     * @throws \Exception
     */
    public function __construct($settings)
    {
        $font = (file_exists($settings['font']) ? $settings['font'] : $this->fontPath . $settings['font']) . '.ttf';
        if (!file_exists($font)) {
            throw new \Exception('Invalid font \'' . $font . '\'');
        }

        $this->width = $settings['width'];
        $this->height = $settings['height'];
        $this->boxColor = $settings['boxColor'];
        $this->xOffset = $settings['xOffset'];
        $this->yOffset = $settings['yOffset'];
        $this->boxColor = Util::hex2rgb($settings['boxColor']);
        $this->fontColor = Util::hex2rgb($settings['fontColor']);

        $this->labelOffsets = explode(',', $settings['labelOffsets']);

        $this->date['time'] = $settings['time'];
        $this->date['futureDate'] = new DateTime(date('r', strtotime($settings['time'])));
        $this->date['timeNow'] = time();
        $this->date['now'] = new DateTime(date('r', time()));

        // create new images
        $this->box = imagecreatetruecolor($this->width, $this->height);
        $this->base = imagecreatetruecolor($this->width, $this->height);

        $this->fontSettings['path'] = $font;
        $this->fontSettings['color'] = imagecolorallocate($this->box, $this->fontColor[0], $this->fontColor[1], $this->fontColor[2]);
        $this->fontSettings['size'] = $settings['fontSize'];
        $this->fontSettings['characterWidth'] = imagefontwidth($this->fontSettings['path']);

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

        $this->images = [
            'box' => $this->box,
            'base' => $this->base,
        ];

        // create empty filled rectangles
        foreach ($this->images as $image) {
            Util::createFilledBox($image, $this->width, $this->height, $this->boxColor);
        }

        $this->createFrames();
    }

    /**
     * Create all of the frames for the countdown timer
     *
     * @return void
     */
    public function createFrames()
    {
        $this->boundingBox = imagettfbbox($this->fontSettings['size'], 0, $this->fontSettings['path'], '00:00:00:00');
        $text_box_dimensions = imagettfbbox($this->fontSettings['size'], 0, $this->fontSettings['path'], '0');

        $this->textBoxWidth = $text_box_dimensions[2];
        $this->textBoxHeight = abs($text_box_dimensions[1] + $text_box_dimensions[7]);

        $this->applyTextToImage($this->base, $this->fontSettings, $this->date);

        // create each frame
        for ($second = 0; $second <= $this->seconds; $second++) {
            $layer = imagecreatetruecolor($this->width, $this->height);
            Util::createFilledBox($layer, $this->width, $this->height, $this->boxColor);

            $this->applyTextToImage($layer, $this->fontSettings, $this->date);
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
    private function applyTextToImage(&$image, array $font, array $date)
    {
        $interval = date_diff(
            $date['futureDate'],
            $date['now']
        );

        if ($date['futureDate'] < $date['now']) {
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
                $this->xOffset + ($this->textBoxWidth * $this->labelOffsets[$key]),
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

        $this->date['now']->modify('+1 second');
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
