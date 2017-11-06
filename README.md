Freeproduct
===========

This extensions allows you to add gifts to your cart. To do allow this, a new action "add a gift" is added to the cart price rules. The development and the function is described in the following two websites:
- http://www.code4business.de/make-a-gift-magento-warenkorbpreisregeln-um-geschenke-erweitern/
- http://www.webguys.de/magento/turchen-21-kostenlose-produkte-uber-warenkorb-preisregeln/

# Supported Product Types
The extension only supports simple and virtual product types, other types or custom options are not supported. The reason is that other product types need additional information that can only be added with IDs. This leads to a way more complicated module; we want to keep this module clean and easy.

# Contribution

I am very happy for people contributing with pull requests. If possible stick to the following coding rules:
- Keep your code as simple and as short as possible
- Use speaking method and variable names - this is a very important (or the primary) source of documentation
- Use observers instead of rewrites wherever possible
- Do not duplicate code; so if you copy code from one place to another you are probably doing something wrong
- Only use comments inside methods if the code is really hard to understand and you cannot make it easier; please comment the methods however
- Use sentences for your commit-messages that start with a verb in past tense and end with a dot, e.g. "Add modman file."

# Magento 2
You can find the Magento 2.x version [here](https://github.com/code4business/freeproduct2).

# Current localizations:
- de_DE
- es_ES
- fr_FR
- nl_NL
- pt_PT
