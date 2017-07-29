Otherworld is a society simulator / PBBG.

How to set up your local environment

I recommend using xampp.

If you don't know how it works, read this article:

https://austin.passy.co/2012/setting-up-virtual-hosts-wordpress-multisite-with-xampp-on-windows-7/

Once you have xampp figured out, you will need to edit the addresses in root.inc.php to match your own local setup.

Also you will need a function called myHash($pw) inside _private/hashing.inc.php . It can be just "return md5($pw)" or something more complicated. After you have this, you can generate a password using testpasscreator.php . Once you have a password, you need to insert a user with the hash in your database, so you can log in. ...Oh, right, you need a database too. Well, we're not currently sharing the database structure with just anybody. If you want the structure of the database, you will have to contact the project leader or figure out the table and column names from the queries in the script. We both know which one is easier.