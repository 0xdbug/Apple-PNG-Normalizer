Based on [ipin.py](https://gist.github.com/urielka/3609051) by Axel E. Brzostowski.

# APN
A PHP implementation of `ipin.py`.

I created this for an in-house project but decided to make it public.

APN is a PHP class that converts Apple's proprietary PNG extension back to a general readable PNG format. These PNG files are typically found in IPA files after being compressed by Apple. While software like Finder and Safari can read them, many others, like Firefox, cannot.

Checkout `input.png` and `output.png`.

# Usage
To use APN, simply include the `APN.php` file in your PHP project and utilize the provided static methods.
