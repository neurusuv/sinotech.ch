// Delay JS Script
var wpcEvents = ['keydown', 'mousemove', 'touchmove', 'touchstart', 'touchend', 'wheel', 'visibilitychange', 'resize'];
wpcEvents.forEach(function (eventName) {
    window.addEventListener(eventName, preload);
});

window.addEventListener('load', function () {
    var scrollTop = window.scrollY;
    if (scrollTop > 60) {
        preload();
    }
});

function preload() {
    var all_iframe = [].slice.call(document.querySelectorAll("iframe.wpc-iframe-delay"));
    var all_scripts = [].slice.call(document.querySelectorAll('[type="wpc-delay-script"]'));
    var all_styles = [].slice.call(document.querySelectorAll('[rel="wpc-stylesheet"]'));
    var mobile_styles = [].slice.call(document.querySelectorAll('[rel="wpc-mobile-stylesheet"]'));

    var mobileStyles = [];
    var styles = [];
    var iframes = [];


    mobile_styles.forEach(function (element, index) {
        mobileStyles.push(element);
    });

    all_styles.forEach(function (element, index) {
        styles.push(element);
    });

    all_iframe.forEach(function (element, index) {
        iframes.push(element);
    });

    var customPromiseFlag = [];

    var i = 0;
    all_scripts.forEach(function (element, index) {
        i++;
        if (!element.hasAttribute('src')) {
            try {
                setTimeout(function () {
                    element.setAttribute('type', 'text/javascript');
                    eval(element.textContent);
                }, index * 5);
            } catch (error) {
            }
        } else {
            console.log(element);
            // External script
            var elementID = element.id;
            var jsBefore = document.getElementById(elementID + '-before');
            var jsAfter = document.getElementById(elementID + '-after');
            var jsExtra = document.getElementById(elementID + '-extra');

            if (jsBefore !== null) {
                jsBefore.setAttribute('type', 'text/javascript');
                //=eval(jsBefore.textContent);
            }

            if (jsAfter !== null) {
                jsAfter.setAttribute('type', 'text/javascript');
                // eval(jsAfter.textContent);
            }

            if (jsExtra !== null) {
                jsExtra.setAttribute('type', 'text/javascript');
                //eval(jsExtra.textContent);
            }

            //setTimeout(function () {
            var new_element = document.createElement('script');
            new_element.setAttribute('src', element.getAttribute('src'));
            document.body.appendChild(new_element);

            // if (jsAfter !== null) {
            //     var new_element = document.createElement('script');
            //     new_element.textContent = jsAfter.textContent;
            //     document.body.appendChild(new_element);
            // }
//            }, i * 5);
        }

    });

    styles.forEach(function (element, index) {
        var promise = new Promise(function (resolve, reject) {
            element.setAttribute('rel', 'stylesheet');
            element.setAttribute('type', 'text/css');
            element.addEventListener('load', function () {
                resolve();
            });

            element.addEventListener('error', function () {
                reject();
            });
        });
        customPromiseFlag.push(promise);
    });

    styles = [];

    iframes.forEach(function (element, index) {
        var promise = new Promise(function (resolve, reject) {
            var iframeUrl = element.getAttribute('data-src');
            element.setAttribute('src', iframeUrl);
            element.addEventListener('load', function () {
                resolve();
            });

            element.addEventListener('error', function () {
                reject();
            });
        });
        customPromiseFlag.push(promise);
    });

    iframes = [];

    mobileStyles.forEach(function (element, index) {
        var promise = new Promise(function (resolve, reject) {
            element.setAttribute('rel', 'stylesheet');
            element.setAttribute('type', 'text/css');
            element.addEventListener('load', function () {
                resolve();
            });

            element.addEventListener('error', function () {
                reject();
            });
        });
        customPromiseFlag.push(promise);
    });

    mobileStyles = [];

    Promise.all(customPromiseFlag).then(function () {
        var criticalCss = document.querySelector('#wpc-critical-css');
        if (criticalCss) {
            criticalCss.remove();
        }
    }).catch(function () {
        styles.forEach(function (element, index) {
            element.setAttribute('rel', 'stylesheet');
            element.setAttribute('type', 'text/css');
        });
    });

    wpcEvents.forEach(function (eventName) {
        window.removeEventListener(eventName, preload);
    });

}