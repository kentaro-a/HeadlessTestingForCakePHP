# HeadlessTestingForCakePHP
Headless Browser Testing With Selenium For CakePHP3 Apps.
This uses Facebook Webdriver and Selenium Standalone Server.


## Installation
```
$ composer require kentaro-a/headless-testing-for-cakephp
```

 You have to get google-chrome.
```
$ curl https://intoli.com/install-google-chrome.sh | bash
```



## Usage

#### Launch Selenium Standalone Server as Daemon.
```
$ vendor/se/selenium-server-standalone/bin/selenium-server-standalone -port 60002 -log "/tmp/selenium.log" > /dev/null 2>&1 &
```



#### Testing
Before testing you have to make test code in tests/TestCase/
More about test code, you can see example.
Execute cakephp3 test like below.
```
$ vendor/bin/phpunit tests/TestCase/HeadlessSampleTest.php --filter test001
```



### Tips

- Make sure processes
```
$ ps aux|grep -e selenium -e chrome
```

- Kill chrome and selenium
If you cannot kill the processes, you should try "kill -9 process" forcibly.
```
$ pkill -f chrome ; pkill -f selenium
```

- If you cannot create driver make sure whether your port is occupied or not by zonbie.
```
$ lsof -i:60002
```


- Xpath tip
Chrome Addon [ChroPath](https://chrome.google.com/webstore/detail/chropath/ljngjbnaijcbncmcnjfhigebomdlkcjo) is the easiest way to get a xpath of specific element.


