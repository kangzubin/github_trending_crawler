# PHP Github Trending Crawler

## 简介

由于 GitHub 官方提供的 [API](https://developer.github.com/) 并不包含 [GitHub Trending](https://github.com/trending) 相关的接口，而作为好学开发者，我们都会去关注 `Trending` 趋势，以获取最近 GitHub 上相关编程语言有哪些优秀项目和哪些开发者最火。另外，我们在开发第三方 GitHub App 时，通常也需要展示 `Trending` 数据，如果直接在客户端抓取解析，吃力不讨好，且国内访问速度较慢，此时就需要服务端提供相关接口来解决问题。

本仓库提供了一个 `PHP` 小爬虫，用于在服务端定时抓取解析 `GitHub Trending` 数据并缓存，以提供给客户端快速（秒级）的查询接口。它可以分别以天（`Daily`）、周（`Weekly`）、月（`Monthly`）三个维度抓取各种编程语言下最受关注的 `Repositories` 和 `Developers`。

这个小爬虫的核心代码主要是 `crawler` 文件夹下的 `simple_html_dom.php` 和 `github_trending_crawler.php` 两个文件。

### simple_html_dom.php : 

此文件来自开源库 [PHP Simple HTML DOM Parser](https://sourceforge.net/projects/simplehtmldom/)，它提供了一个简单易用单，功能强大的 `HTML DOM` 解析方法，便于我们用 `PHP` 抓取网页的 `HTML` 并进行分析。

### github_trending_crawler.php : 

此文件主要用于抓取解析 `GitHub Trending` 数据，包含以下方法：

* **gt_get_html_dom()** : 此方法需要传入一个 `url` 参数，用于获取指定 `url` 下的 `HTML`，并返回一个 `simple_html_dom` 对象，方便后续解析 `HTML` 中不同标签里的数据。

* **gt_get_top_languages()** : 此方法用于获取 `GitHub Trending` 页面右侧推荐的目前较流行的编程语言（**注：登录状态下每个人看到的结果可能不同，此方法是在未登录下抓取的**），返回一个 `languages` 数组，数组中的每一项包含 `name` 和 `id` 两个字段，大致如下：

```json
{"languages":[{"name":"C++","id":"c++"},{"name":"PHP","id":"php"}, ... ]}
```

* **gt_get_all_languages()** : 此方法用于获取 `GitHub` 所有的编程语言，同样返回一个 `languages` 数组，结构与上述类似。

* **gt_get_repositories()** : 此方法接收两个参数 `lang` 和 `since`，其中 `lang` 的取值来自上述 `languages` 中返回的 `id`，`since` 的取值包括 `daily`，`weekly`，`monthly`，它用于获取指定时间维度下，某一编程语言最受关注的**开源项目**，返回一个 `repositories` 数组，大致如下：

```json
{
"repositories": [{
    "author": "airbnb",
    "title": "lottie-ios",
    "url": "https://github.com/airbnb/lottie-ios",
    "description": "An iOS library to natively render After Effects vector animations",
    "language": "Objective-C",
    "stars": "13,084", 
    "forks": "1,683",
    "newStars": "25 stars today",
    "contributors": [{
      "id": "buba447",
      "avatar": "https://avatars1.githubusercontent.com/u/1163980"
    }, {
      "id": "welshm",
      "avatar": "https://avatars0.githubusercontent.com/u/3903024"
    }, {
        "id": "valeriyvan",
        "avatar": "https://avatars0.githubusercontent.com/u/1630974"
    }, {
        "id": "hansemannn",
        "avatar": "https://avatars3.githubusercontent.com/u/10667698"
    }, {
        "id": "fnazrala",
        "avatar": "https://avatars2.githubusercontent.com/u/2164816"
    }]
  },
  
  ...
  
]}
```

* **gt_get_developers()** : 此方法同样接收两个参数 `lang` 和 `since`，取值与 `gt_get_repositories()` 方法类似，它用于获取指定时间维度下，某一编程语言最受欢迎的**开发者**，返回一个 `developers` 数组，大致如下：

```json
{
"developers": [{
    "url": "https://github.com\/facebook",
    "avatar": "https://avatars2.githubusercontent.com/u/69631",
    "name": "Facebook",
    "id": "facebook",
    "repository": {
        "url": "https://github.com/facebook/Shimmer",
        "title": "Shimmer",
        "desp": "An easy way to add a simple, shimmering effect to any view in an iOS app."
    }
  },

  ...

]}
```

**注意**：如果 `GitHub Trending` 访问失败、超时，或者某一维度下页面没有数据，或者解析异常，上述方法都会返回 `null`，**而且如果以后 `GitHub Trending` 页面的 `HTML` 结构发生变化，上述方法的解析逻辑也要做相应的修改。**

## 使用示例

为了方便大家正确使用 `github_trending_crawler.php`，我写了一个 `example.php` 示例脚本，
把 `crawler` 文件夹和 `example.php` 文件放到你的 `Web` 目录下，并访问以下 `URL` 路径，即可获得相应结果，具体的逻辑详见 `example.php` 文件，不再赘述（在 PHP 5.6 测试通过）。

```
// 默认获取今日所有编程语言下开源项目的 Trending 数据
https://path/to/example.php

// 获取今日 Swift 语言下开源项目的 Trending 数据
https://path/to/example.php?lang=swift&since=daily

// 获取本月 Swift 语言下开源项目的 Trending 数据
https://path/to/example.php?action=repositories&lang=swift&since=monthly

// 获取本周 Objective-C 语言下开发者的 Trending 数据
https://path/to/example.php?action=developers&lang=objective-c&since=weekly

// 获取最受关注的编程语言和所有的编程语言列表
https://path/to/example.php?action=top_languages
https://path/to/example.php?action=all_languages
```

 其中，`URL` 的 `query` 请求参数的默认值如下：
 * lang: `null` // 默认获取全部语言的结果
 * since: `daily` // 默认获取今日的数据
 * action: `repositories` // 默认获取

当然，如果每次访问 `example.php` 脚本都去实时抓取一遍数据，显然是很耗时的，而且 `GitHub Trending` 页面的更新频率也不是很快，所以我们可以在服务端通过 `Redis` 把数据缓存下来，下次访问时如果缓存中有数据就直接返回，可以大大提高访问速度，详见 `example_with_redis.php` 文件。

**另外，你可以在你的服务端开启一些 `Crontab` 定时任务，定时去抓取数据更新缓存，以便提供给客户端的接口可以快速响应。**

注意，如果你的 `PHP` 脚本在运行过程中报如下错误：

```
file_get_contents(): SSL operation failed with code 1. OpenSSL Error messages: error:1407742E:SSL routines:SSL23_GET_SERVER_HELLO:tlsv1 alert protocol version in /path/to/simple_html_dom.php on line 75.
```

那是因为 `February 01, 2018` 起，GitHub 限制 `HTTPS` 访问只能用 `TLSv1.2`，详见[这里](https://githubengineering.com/crypto-removal-notice/)，所以你可能需要更新一下你服务端的 OpenSSL 版本。

## 测试接口

为了让大家能够更直观地感受上述小爬虫所带来的效果，我在我自己的服务器上部署了一套环境，大家可以直接访问以下示例 `URL` 获得相关结果：

// 默认获取今日所有编程语言下开源项目的 Trending 数据
[https://app.kangzubin.com/trending/test](https://app.kangzubin.com/trending/test)

// 获取今日 Swift 语言下开源项目的 Trending 数据
[https://app.kangzubin.com/trending/test?lang=swift&since=daily](https://app.kangzubin.com/trending/test?lang=swift&since=daily)

// 获取本月 Swift 语言下开源项目的 Trending 数据
[https://app.kangzubin.com/trending/test?action=repositories&lang=swift&since=monthly](https://app.kangzubin.com/trending/test?action=repositories&lang=swift&since=monthly)

// 获取本周 Objective-C 语言下开发者的 Trending 数据
[https://app.kangzubin.com/trending/test?action=developers&lang=objective-c&since=weekly](https://app.kangzubin.com/trending/test?action=developers&lang=objective-c&since=weekly)

// 获取最受关注的编程语言和所有的编程语言列表
[https://app.kangzubin.com/trending/test?action=top_languages](https://app.kangzubin.com/trending/test?action=top_languages)
[https://app.kangzubin.com/trending/test?action=all_languages](https://app.kangzubin.com/trending/test?action=all_languages)

**重要说明**：第一次访问上述接口获取相关数据时，如果缓存中有数据，就直接返回；如果缓存中没有数据，会实时抓取 `GitHub Trending` 页面数据进行解析并缓存，此时接口返回的速度回比较慢。**另外，由于是测试用，所以数据一旦缓存后，我并没有去定时更新，所以上述测试接口有时候返回的数据可能是过时的。**

**！！！上述接口仅供大家体验，不建议在你的服务或 App 中直接使用，因为我可能会随时下线。**

## BY THE WAY

如果你问我写爬虫为什么采用 `PHP` 而不用 `Python`，那当然是因为 `PHP` 是... 😷

![PHP is the best language in the world](https://img.kangzubin.cn/blog/PHP.jpg)

图片来自[《神秘的程序员们》](http://blog.xiqiao.info/category/programmers)

## LICENSE

This repositorie is released under the MIT license. See [LICENSE](https://github.com/kangzubin/github_trending_crawler/blob/master/LICENSE) for details.

