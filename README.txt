# wppt-emailer (WordPress Phil Tanner's Emailer)

Yet another WordPress plugin that helps resolve email woes.

## Getting Started

These instructions will get you a copy of the project up and running on your local machine for development and testing purposes. See deployment for notes on how to deploy the project on a live system.

### Prerequisites

A running copy of WordPress, with Administrator login details. 

This plugin should be platform (Windows/Mac/*Nix) independent, and doesn't require any particular versions of PHP to run.

It does request jQuery and jQueryUI to be loaded from WordPress, though these should already be bundled in all standard (i.e. non-very-specific instances where they've been deliberately disabled) installs.

A single jQueryUI CSS stylesheet is requested from Google's CDN, but the code will work offline (although your emails won't!), but look slightly ugly.

### Installing

Download the lastest zipped copy of the plugin from GitHub:
https://github.com/PhilTanner/wppt-emailer/archive/master.zip

Log in to your WordPress dashboard, navigate to the Plugins menu, and click [Add New] at the top of the page.

Click [Upload Plugin]

Click [Choose File]

Select the ```wppt-emailer-master.zip``` file you've just downloaded.

Click [Install Now]

`` Do NOT click [Activate Plugin]``

Return to the Plugins list, and click [Activate] under the "Phil Tanner's Emailer" item.


## Running the tests

Select the [Phil's Emailer] link in the Dashboard navigation.

Enter your SMTP mail settings, and click the Test button.

If it works, you will get a success statement appear on the right. If not - hopefully a meaningful message as to why not.

## Deployment

Add additional notes about how to deploy this on a live system

## License

This project is licensed under the GPL3 License - see the [LICENSE.md](LICENSE.md) file for details

