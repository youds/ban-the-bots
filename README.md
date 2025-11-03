## How It Works

Firstly, imagine we are one of these bots. They visit a page and literally click all the links adhoc then move onto to the next site. Now, imagine the website has a blackhole. Bots would naturally enter the obis. Upon visiting the blackhole URL, they are banned from there on in.

What about Google, et al? Well, they follow robots.txt protocol so assuming that is the case our blackhole is ignored. Therefore, any bot that listens to your robots.txt rules will be allowed to surf - in the fashion it defines.

## First Stage

### ARE U A B0T?!

Bots are supposed to check robots.txt, so search engines like Google and so on would not visit those pages as per specification. With this in mind, we have a way of filtering good bots vs. bad bots.

## Second Stage 

### BLACKHOLE

Now, imagine a blackhole. This would be a random URL visible only in the website code. On the website, it is not seen anywhere. So who would visit it? Bots not listening to robots.txt of course!

## Third Stage

### SRSLY, THEY'RE FRIED

Once they visit the blackhole the IP address will be blacklisted and all future attempts to visit your web resource would be denied.

## Get The Code

Run `composer require youds/ban-the-bots` then in your codebase simply instantiate the class `$badBots = new BanTheBots();` and call the method `$badBots->apply();`. Simple as that.

## Central API

The `youds/ban-the-bots` package uses the Central API to manage blacklisted IP addresses and bot behavior. This API provides a centralized platform for tracking and managing bot activity across multiple websites.

## Other Languages

Please provide other language support, based on the PHP pack. We need your help!

