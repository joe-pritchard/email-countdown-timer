# Email Countdown Timer

> Create an animated countdown timer for use within HTML emails

This is a fork of https://github.com/woolm110/email-countdown-timer, modified to work with composer and be 
a little less opinionated about how its used (it just spits out the gif as a string for you to do with as you please) 

## Installation
`composer require joe-pritchard/email-countdown-timer`

## Usage
- Generate a GIF countdown timer using the CountdownTimer class:
```$php
$gif = new CountdownTimer($settings);
$gif->getAnimation(); // this is the raw gif as a string
```

## Settings

Use the following keys in the settings array to modify the countdown timer to fit your needs. 
- time (string) - This is the time being counted down to 
- width (int)
- height (int)
- boxColor (hex colour)
- font (string)
- fontColor (hex colour)
- fontSize (int)
- xOffset (int)
- yOffset (int)
- labelOffsets (int[])

### Fonts

Any font file can be used as the base font for the countdowm timer. To use a custom font reference its absolute path in the settings parameter `font`. *Note: fonts must be uploaded using the `ttf` file extension*.
