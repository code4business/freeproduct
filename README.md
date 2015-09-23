freeproduct
===========

This extensions allows you to add gifts to your cart. To do allow this, a new action "add a gift" is added to the cart price rules. The development and the function is described in the following two websites:
- http://www.code4business.de/make-a-gift-magento-warenkorbpreisregeln-um-geschenke-erweitern/
- http://www.webguys.de/magento/turchen-21-kostenlose-produkte-uber-warenkorb-preisregeln/

I am very happy for people contributing with pull requests. If possible stick to the following coding rules:
- Keep your code as simple and as short as possible
- Use speaking method and variable names - this is a very important (or the primary) source of documentation
- Use observers instead of rewrites wherever possible
- Do not duplicate code; so if you copy code from one place to another you are probably doing something wrong
- Only use comments inside methods if the code is really hard to understand and you cannot make it easier; please comment the methods however
- Use sentences for your commit-messages that start with a verb in past tense and end with a dot, e.g. "Add modman file."

Current limitations:
- ksbomj found the problem that you cannot use bundles as gifts; again I am happy about further contributions
