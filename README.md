# SocialNetworks

php composer.phar update --optimize-autoloader --no-dev


Project to work with SocialNetworks to test the method.
# upload to preproduction
appcfg.py update .

# run in pre-preduction
https://testsocialnetworks-dot-ammlab-cloudframework-io.appspot.com/api

# gzip bug in abraham/twitteroauth php library
Owing to that bug, the next line in vendor/abraham/twitteroauth/src/TwitterOAuth.php must be commented:
line 363 => CURLOPT_ENCODING => 'gzip',
( https://github.com/abraham/twitteroauth/pull/408 )