freeproduct
===========

This extensions allows you to add gifts to your cart. To do allow this, a new action "add a gift" is added to the cart price rules. The development and the function is described in the following two websites:
- http://www.code4business.de/make-a-gift-magento-warenkorbpreisregeln-um-geschenke-erweitern/
- http://www.webguys.de/magento/turchen-21-kostenlose-produkte-uber-warenkorb-preisregeln/

I am very happy for people contributing with pull requests. If possible stick to the following coding rules:
- Keep your code as simple and as short as possible
- Use speaking method and variable names - this is a very important (or the primary) source of documentation
- Use observers instead of rewrites wherever possible
- Do not duplicate code; so if you copy code from one place to another you are properly doing something wrong
- Only use comments inside methods if the code is really hard to understand and you cannot make it easier; please comment the methods however
- Use sentences for your commit-messages that start with a verb in past tense and end with a dot, e.g. "Add modman file."

Current limitations:
- The module does not allow to use gifts that you can otherwise put into the cart as well
-- For practical reasons this will not be useful with a "real gift" but in some other cases it might be useful
-- C4rter contributed a patch: https://github.com/C4rter/freeproduct/commit/d6b72b5d673c517cf4d474a1fe12b2c2202bfacf; I did not integrate it because it is rewriting Quote-Item and might easily conflict with other extensions; you can integrate it yourself if you need it
-- I tried some other ways and was not yet successful; I am happy for further contributions
- ksbomj found the problem that you cannot use bundles as gifts; again I am happy about further contributions
