# Email Countdown Timer

> Create an animated countdown timer for use within HTML emails

This is a fork of https://github.com/woolm110/email-countdown-timer, modified to work with composer and be 
a little less opinionated about how its used (it just spits out the gif as a string for you to do with as you please).

It also adds the option to pass in a background image to use behind the countdown instead of a solid colour.

## Installation
`composer require joe-pritchard/email-countdown-timer`

## Usage
- Generate a GIF countdown timer using the CountdownTimer class:
```$php
$gif = new CountdownTimer($settings, $target);
$gif->getAnimation(); // this is the raw gif as a string
```

## Settings

Use the following keys in the settings array to modify the countdown timer to fit your needs. 
- width (int)
- height (int)
- boxColor (hex colour)
- font (string)
- fontColor (hex colour)
- fontSize (int)
- labelOffsets (float[]) eg 0.5,3,5.4,7.6 - each number is applied one of the labels (Days, Hrs, Mins, Secs) respectively
                         and is used as a multiplier on the width of a single character to push the label to the right 
                         (for example, 0.5 moves the label to the right relative to xOffset by the half width of a 
                         character in the time)

The second argument is a DateTime object representing a time in the future to count down to.

The third argument is optional, and is the absolute path to a background image to use instead of a coloured box.
If a background image is provided, then the width, height, and boxColor values will be ignored 
New sizes will be calculated based on the size of the image.

## Example

```$php

$target = new \DateTime('tomorrow');
$settings = [
    'time' => $request->query('time'),
    'width' => 640,
    'height' => 110,
    'boxColor' => '#000',
    'font' => 'BebasNeue',
    'fontColor' => '#fff',
    'fontSize' => 60,
    'xOffset' => 155,
    'yOffset' => 70,
    'labelOffsets' => "0.5,3,5.2,7.6",
];

$gif = new CountdownTimer($settings, $target);

header('Content-Type', 'image/gif');
header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0');
header('Pragma', 'no-cache');

echo $gif->getAnimation();
```

### Fonts

Any font file can be used as the base font for the countdowm timer. To use a custom font reference its absolute path in the settings parameter `font`. *Note: fonts must be uploaded using the `ttf` file extension*.
