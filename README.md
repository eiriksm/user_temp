# user_temp
[![Build Status](https://travis-ci.org/eiriksm/user_temp.svg)](https://travis-ci.org/eiriksm/user_temp)

Make users able to register temperatures for themselves.

This module is an "attachment" to the blog post about the subject, found at [https://orkjern.com/drupal-iot-code-part-2](https://orkjern.com/drupal-iot-code-part-2).

The module creates a new tab on the user page, for all users with the permission to view own temperatures.

From there you can get your API key, and POST temperatures to the endpoint, located at `<mysite.com>/user/{uid}/user_temp_post`

The endpoint expects a JSON body formatted like so:

```
{
  "temp": "21.6"
}
```

Or to put as a CURL command:

```
curl -H "x-user-temp: yourapikey" http://example.com/user/1/user_temp_post -d '{"temp": "21.6"}'
```
