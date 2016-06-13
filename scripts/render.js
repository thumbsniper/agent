"use strict";

var RenderTarget, GetBaseUrl, RenderPageIfRobotsAllowed, target, GetUrlPath, CheckRobotsTxt, GetThumbnailJob, IsJQuery,
    GetPageProperties, DoRender, Finalize, SendResults, RenderTargetCallback;

var system = require('system');
var args = system.args;

var userAgent = "Phantom.js bot";

var loopCount = 0;
var maxRuns = 0;
var serverUrl;
var resultsSuccessUrl;
var resultsFailureUrl;

var finalUrl;
var finalResponse;
var finalResourceError;


phantom.onError = function(msg, trace) {
    var msgStack = ['PHANTOM ERROR: ' + msg];
    if (trace && trace.length) {
        msgStack.push('TRACE:');
        trace.forEach(function(t) {
            msgStack.push(' -> ' + (t.file || t.sourceURL) + ': ' + t.line + (t.function ? ' (in function ' + t.function +')' : ''));
        });
    }
    console.error(msgStack.join('\n'));
    phantom.exit(1);
};


GetBaseUrl = function(url) {
    var pathArray = url.split( '/' );
    var protocol = pathArray[0];
    var host = pathArray[2];
    return protocol + '//' + host + '/';
};


GetUrlPath = function(url) {
    var baseUrl = GetBaseUrl(url);
    var path = url.replace(baseUrl, "/");
    return path !== "" ? path : "/";
};


CheckRobotsTxt = function(content, path) {
    var robotsTxtSplit = content.split('\n');
    var rules = [];
    var recordRules = false;

    for(var i = 0; i < robotsTxtSplit.length; i++) {
        var line = robotsTxtSplit[i].trim();
        var rulePath;

        if(line && line.substring(0, 1) !== "#") {
            if(line.substring(0, 11) === "User-agent:") {
                var ua = line.replace(line.substring(0, 11), '').trim();
                if(ua === '*' || ua === userAgent) {
                    recordRules = true;
                }else {
                    recordRules = false;
                }
            }else if(line.substring(0, 9) === "Disallow:" && recordRules) {
                rulePath = line.replace(line.substring(0, 9), '').trim();
                rules.push({ "rule":"Disallow", "path":rulePath });
            }else if(line.substring(0, 6) === "Allow:" && recordRules) {
                rulePath = line.replace(line.substring(0, 6), '').trim();
                rules.push({"rule":"Allow", "path":rulePath});
            }
        }
    }

    var isAllowed = true;

    if(rules.length > 0) {
        var currentStrength = 0;

        for(var a = 0; a < rules.length; a++) {
            var rulePair = rules[a];

            if(path.substring(0, rulePair.path.length) == rulePair.path) {
                console.log("robots.txt match: " + path + " => " + rulePair.path + " (" + rulePair.rule + ")");
                var strength = rulePair.path.length;
                if(currentStrength < strength) {
                    currentStrength = strength;
                    isAllowed = (rulePair.rule === 'Allow');
                }else if(currentStrength == strength && rulePair.rule === 'Allow') {
                    isAllowed = true;
                }
            }
        }
    }

    console.log("robots.txt result: " + isAllowed);
    return isAllowed;
};


GetThumbnailJob = function(timeout) {
    console.log("");

    if(loopCount >= maxRuns) {
        console.log("exiting gracefully after " + loopCount + " runs.");
        phantom.exit(0);
    }else {
        loopCount++;
    }

    var jobPage = require('webpage');
    var jp = jobPage.create();
    jp.settings.userAgent = "Phantom.js";
    jp.onConsoleMessage = function (msg) { console.log(msg); };

    setTimeout(function() {
        console.log("search next thumbnail job");

        jp.open(serverUrl, function(status) {
            try {
                if(status === "success") {
                    var content = jp.plainText;
                    var target = JSON.parse(content);
                    jp.close();

                    if(target.url) {
                        console.log("target (" + target.id + "): " + target.url);
                        RenderTarget(target, RenderTargetCallback);
                    }else if(target.sleep >= 0) {
                        console.log("sleeping for " + target.sleep + " seconds");
                        GetThumbnailJob(target.sleep);
                    }else {
                        throw "couldn't find result";
                    }
                }else {
                    throw "status = " + status;
                }
            }catch(e) {
                console.log("Exception: " + e);
                jp.close();
                GetThumbnailJob(1);
            }
        });
    }, timeout * 1000);
};


RenderPageIfRobotsAllowed = function(callback, page, target) {
    var baseUrl = GetBaseUrl(target.url);
    var path = GetUrlPath(target.url);

    console.log("checking robots.txt for " + baseUrl);

    var robotsPage = require('webpage');
    var rp = robotsPage.create();
    rp.onConsoleMessage = function (msg) { console.log(msg); };

    rp.open(baseUrl + "robots.txt", function(status) {
        try {
            console.log("opening " + baseUrl + "robots.txt");
            if(status === "success") {
                var content = rp.plainText;
                rp.close();
                var result = CheckRobotsTxt(content, path);
                target['robotsAllowed'] = result;

                if(result) {
                    return DoRender(callback, page, target);
                }else {
                    console.log("forbidden by robots.txt");
                    return Finalize(callback, "error", target)
                }
            }else {
                throw "robots.txt check failed. Continuing.";
            }
        }catch(e) {
            console.log("Exception: " + e);
            rp.close();
            return DoRender(callback, page, target);
        }
    });
};


GetPageProperties = function(page, target) {
    console.log("run getPageProperties function");

    target['contentType'] = finalResponse.contentType;
    target['httpStatusCode'] = finalResponse.status;
    target['httpStatusText'] = finalResponse.statusText;
    target['recordTs'] = finalResponse.time;
    target['resolvedUrl'] = finalResponse.url;

    if(!IsJQuery(page)) {
        console.log("injecting jQuery");
        page.injectJs("https://code.jquery.com/jquery-1.12.4.min.js");
    }

    if(IsJQuery(page)) {
        console.log("run page.evaluate");
        var properties = page.evaluate(function() {
            return {
                "documentTitle":document.title,
                "metaDescription":jQuery('meta[name=description]').attr("content"),
                "metaOgTitle":jQuery('meta[property="og:title"]').attr("content"),
                "metaOgDescription":jQuery('meta[property="og:description"]').attr("content"),
                "metaOgSiteName":jQuery('meta[property="og:site_name"]').attr("content"),
                "metaOgLocale":jQuery('meta[property="og:locale"]').attr("content")
            };
        });

        for(var key in properties) {
            target[key] = properties[key];
        }

        return target;
    }else {
        return target;
    }
};


IsJQuery = function(page) {
    return page.evaluate(function() {
        //localStorage.clear();
        if(typeof jQuery == 'undefined') {
            console.log("jQuery not defined");
            return false;
        }else {
            console.log("jQuery is defined");
            return true;
        }
    });
};


DoRender = function(callback, page, target) {
    var ts = Date.now();

    return page.open(target.url, function(status) {
        try {
            console.log("run page.open function");

            if(status === "success") {
                ts = (Date.now() - ts) / 1000;

                target = GetPageProperties(page, target);
                target['snipeDuration'] = ts;

                //console.log("target debug: " + JSON.stringify(target));
                if(target.httpStatusCode > 399) {
                    throw "http error " + target.httpStatusCode;
                }

                return window.setTimeout((function() {
                    page.evaluate(function(fileType) {
                        var style = document.createElement('style');
                        var text = document.createTextNode('body { background: #fff }');
                        style.setAttribute('type', 'text/css');
                        style.appendChild(text);
                        document.head.insertBefore(style, document.head.firstChild);
                    });

                    console.log("start page.render");
                    target['image'] = page.renderBase64('PNG');
                    return Finalize(callback, status, target)
                }), 200);
            }else {
                throw "some error happened";
            }
        }catch(e) {
            console.log("Exception: " + e);
            page.close();
            target['error'] = e;
            return Finalize(callback, "error", target)
        }
    });
};


Finalize = function(callback, status, target) {
    console.log("run Finalize function");
    callback(status, target);
};


RenderTarget = function(target, callback) {
    console.log("run RenderTarget function");
    var webpage, page, retrieve;

    webpage = require('webpage');
    page = null;
    finalUrl = target.url;

    retrieve = function() {
        console.log("run retrieve function");
        page = webpage.create();
        page.onConsoleMessage = function (msg) { console.log(msg); };
        page.viewportSize = { width: 1600, height: 1200 };
        page.clipRect = { top: 0, left: 0, width: page.viewportSize.width, height: page.viewportSize.height };
        page.settings.userAgent = userAgent;

        page.onUrlChanged = function(targetUrl) {
            finalUrl = targetUrl;
        };

        page.onResourceReceived = function(response) {
            if(response.url == finalUrl) {
                finalResponse = response;
                //console.log('Response (#' + response.id + ', stage "' + response.stage + '"): ' + JSON.stringify(response));
            }
        };

        page.onResourceError = function(resourceError) {
            finalResourceError = resourceError;
        };

        RenderPageIfRobotsAllowed(callback, page, target);
    };

    return retrieve();
};


SendResults = function(jobStatus, target, nextSleep) {
    console.log("SendResults start");

    var webPage = require('webpage');
    var page = webPage.create();
    page.settings.userAgent = "Phantom.js";
    var settings = {
        operation: "POST",
        encoding: "utf8",
        headers: {
            "Content-Type": "application/json"
        },
        data: JSON.stringify(target)
    };

    var resultsUrl;

    if(jobStatus === "success") {
        resultsUrl = resultsSuccessUrl;
    }else {
        resultsUrl = resultsFailureUrl;
    }

    return page.open(resultsUrl, settings, function(status) {
        try {
            console.log('Status: ' + status);
            console.log(page.plainText);
            page.close();
        }catch(e) {
            console.log("Exception: " + e);
            page.close();
        }

        GetThumbnailJob(nextSleep);
    });
};


RenderTargetCallback = function(status, target) {
    if(status === "success") {
        console.log("=============");
        console.log("Results:");
        console.log("id: " + target.id);
        console.log("url: " + target.url);
        console.log("resolved url: " + target.resolvedUrl);
        console.log("robotsAllowed: " + target.robotsAllowed);
        console.log("HTTP Status code: " + target.httpStatusCode);
        console.log("HTTP Status text: " + target.httpStatusText);
        console.log("snipeDuration: " + target.snipeDuration);
        console.log("Content type: " + target.contentType);
        console.log("Record TS: " + target.recordTs);
        console.log("title: " + target.documentTitle);
        console.log("description: " + target.metaDescription);
        console.log("og:title: " + target.metaOgTitle);
        console.log("og:description: " + target.metaOgDescription);
        console.log("og:site_name: " + target.metaOgSiteName);
        console.log("og:locale: " + target.metaOgLocale);
        //console.log("image: " + target.image);
        console.log("=============");

        SendResults(status, target, 0);
    }else {
        console.log("Unable to render '" + target.url + "'");
        console.log(JSON.stringify(target));
        //console.log(JSON.stringify(finalResourceError));

        SendResults(status, target, 1);
    }
};

////////////////////

if(args[1]) {
    console.log("setting maxRuns = " + args[1]);
    maxRuns = args[1];
}else {
    console.log("setting maxRuns = 10");
    maxRuns = 10;
}

if(args[2]) {
    console.log("setting serverUrl = " + args[2]);
    serverUrl = args[2];
}else {
    console.log("missing serverUrl");
    phantom.exit(1);
}

if(args[3]) {
    console.log("setting resultsSuccessUrl = " + args[3]);
    resultsSuccessUrl = args[3];
}else {
    console.log("missing resultsSuccessUrl");
    phantom.exit(1);
}

if(args[4]) {
    console.log("setting resultsFailureUrl = " + args[4]);
    resultsFailureUrl = args[4];
}else {
    console.log("missing resultsFailureUrl");
    phantom.exit(1);
}

GetThumbnailJob(0);
