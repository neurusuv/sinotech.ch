// IsMobile
var mobileWidth;
var isMobile = false;
var jsDebug = false;
var isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);

if (ngf298gh738qwbdh0s87v_vars.js_debug == 'true') {
    jsDebug = true;
}

function checkMobile() {
    if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || window.innerWidth <= 680) {
        isMobile = true;
        mobileWidth = window.innerWidth;
    }
}

checkMobile();
var preloadRunned = false;
var windowWidth = window.innerWidth;

window.addEventListener('DOMContentLoaded', function () {
    registerEvents();
});

// Delay JS Script
var wpcEvents = ['keydown', 'mousemove', 'touchmove', 'touchstart', 'touchend', 'wheel', 'visibilitychange', 'resize'];
function registerEvents() {
    wpcEvents.forEach(function (eventName) {

        if (jsDebug) {
            console.log('Event registered: ' + eventName);
        }

        window.addEventListener(eventName, function () {
            preloadTimeout(eventName);
        });
    });
}

function preloadTimeout(event) {

    if (jsDebug) {
        console.log('Running Preload Timeout');
    }

    if (!preloadRunned) {

        if (jsDebug) {
            console.log('Event name in preload is ');
            console.log(event);
            console.log('Before width: ' + windowWidth);
            console.log('After width: ' + window.innerWidth);
        }

        if (event == 'resize') {
            if (windowWidth === window.innerWidth) {
                // Nothing changed, ignore the event
                return false;
            }
        }

        preloadRunned = true;
        setTimeout(function () {
            if (jsDebug) {
                console.log('Inside Preload Timeout');
            }
            preload();
            removeEventListeners();
        }, 50);
    }
}

function removeEventListeners() {
    wpcEvents.forEach(function (eventName) {
        window.removeEventListener(eventName, preloadTimeout);
    });
}

window.addEventListener('load', function () {
    var scrollTop = window.scrollY;
    if (scrollTop > 60) {
        preload();
    }
});

function preload() {
    var iframes = [].slice.call(document.querySelectorAll("iframe.wpc-iframe-delay"));
    var allScripts = [].slice.call(document.querySelectorAll('script[type="wpc-delay-script"]'));
    var styles = [].slice.call(document.querySelectorAll('[rel="wpc-stylesheet"]'));
    var mobileStyles = [].slice.call(document.querySelectorAll('[rel="wpc-mobile-stylesheet"]'));

    var wpScripts = [];
    var customPromiseFlag = [];

    if (jsDebug) {
        console.log('Found scripts');
        console.log(allScripts);
    }

    // Move wp-include scripts into wpScripts array to load them first
    for (var i = 0; i < allScripts.length; i++) {
        var script = allScripts[i];
        if (script.src && script.src.includes('wp-includes')) {
            wpScripts.push(script);
            allScripts.splice(i, 1);
            i--;
        }
    }

    if (jsDebug) {
        console.log('Found WP scripts');
        console.log(wpScripts);
    }

    wpScripts.forEach(function (element, index) {
        var newScript = document.createElement('script');
        newScript.setAttribute('src', element.getAttribute('src'));
        newScript.setAttribute('type', 'text/javascript');
        document.body.appendChild(newScript);
    });

    wpScripts = [];

    allScripts.forEach(function (element, index) {
        var elementID = element.id;

        if (jsDebug) {
            console.log(element);
        }



        if (!element.hasAttribute('src') && !element.id.includes('-before') && !element.id.includes('-after') && !element.id.includes('-extra')) {
            var newElement = document.createElement('script');
            newElement.textContent = element.textContent;
            newElement.setAttribute('type', 'text/javascript');
            newElement.async = false;
            document.body.appendChild(newElement);
        } else {
            // External script
            var jsBefore = document.getElementById(elementID + '-before');
            var jsAfter = document.getElementById(elementID + '-after');
            var jsExtra = document.getElementById(elementID + '-extra');

            if (jsBefore !== null) {
                var newElementBefore = document.createElement('script');
                newElementBefore.textContent = jsBefore.textContent;
                newElementBefore.setAttribute('type', 'text/javascript');
                newElementBefore.async = false;
                document.body.appendChild(newElementBefore);
            }

            if (jsAfter !== null) {
                //jsAfter.setAttribute('type', 'text/javascript');
                // eval(jsAfter.textContent);
            }

            if (jsExtra !== null) {
                var newElementExtra = document.createElement('script');
                newElementExtra.textContent = jsExtra.textContent;
                newElementExtra.setAttribute('type', 'text/javascript');
                newElementExtra.async = false;
                document.body.appendChild(newElementExtra);
            }

            if (element !== null) {
                var new_element = document.createElement('script');
                if (element.getAttribute('src') !== null) {
                    new_element.setAttribute('src', element.getAttribute('src'));
                    new_element.setAttribute('type', 'text/javascript');
                    new_element.async = false;
                    document.body.appendChild(new_element);
                } else {
                    new_element.textContent = element.textContent;
                    new_element.setAttribute('type', 'text/javascript');
                    new_element.async = false;
                    document.body.appendChild(new_element);
                }

                new_element.onload = function () {
                    if (jsAfter !== null) {
                        var new_elementAfter = document.createElement('script');
                        new_elementAfter.textContent = jsAfter.textContent;
                        new_elementAfter.setAttribute('type', 'text/javascript');
                        document.body.appendChild(new_elementAfter);
                        jsAfter.remove();
                    }
                };
            }


            if (element !== null) {
                element.remove();
            }

            if (jsBefore !== null) {
                jsBefore.remove();
            }

            if (jsExtra !== null) {
                jsExtra.remove();
            }
        }

        // Remove the element from the array
        //allScripts.splice(index, 1);
    });

    allScripts = [];

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
// Lazy
var lazyImages = [];
var active;
var activeRegular;
var browserWidth;
var jsDebug = 0;

function load() {
    browserWidth = window.innerWidth;
    lazyImages = [].slice.call(document.querySelectorAll("img"));
    elementorInvisible = [].slice.call(document.querySelectorAll("section.elementor-invisible"));
    active = false;
    activeRegular = false;
    lazyLoad();
}

if (ngf298gh738qwbdh0s87v_vars.js_debug == 'true') {
    jsDebug = 1;
    console.log('JS Debug is Enabled');
}

function lazyLoad() {
    if (active === false) {
        active = true;

        elementorInvisible.forEach(function (elementorSection) {
            if ((elementorSection.getBoundingClientRect().top <= window.innerHeight
                    && elementorSection.getBoundingClientRect().bottom >= 0)
                && getComputedStyle(elementorSection).display !== "none") {
                elementorSection.classList.remove('elementor-invisible');

                elementorInvisible = elementorInvisible.filter(function (section) {
                    return section !== elementorSection;
                });
            }
        });

        lazyImages.forEach(function (lazyImage) {

            if (lazyImage.classList.contains('wps-ic-loaded')) {
                return;
            }

            if ((lazyImage.getBoundingClientRect().top <= window.innerHeight + 1000
                    && lazyImage.getBoundingClientRect().bottom >= 0)
                && getComputedStyle(lazyImage).display !== "none") {

                imageExtension = '';
                imageFilename = '';

                if (typeof lazyImage.dataset.src !== 'undefined') {

                    if (lazyImage.dataset.src.endsWith('url:https')) {
                        return;
                    }

                    imageFilename = lazyImage.dataset.src;
                    imageExtension = lazyImage.dataset.src.split('.').pop();
                } else if (typeof lazyImage.src !== 'undefined') {
                    if (lazyImage.src.endsWith('url:https')) {
                        return;
                    }
                    imageFilename = lazyImage.dataset.src;
                    imageExtension = lazyImage.src.split('.').pop();
                }


                if (imageExtension !== '') {
                    if (imageExtension !== 'jpg' && imageExtension !== 'jpeg' && imageExtension !== 'gif' && imageExtension !== 'png' && imageExtension !== 'svg' && lazyImage.src.includes('svg+xml') == false && lazyImage.src.includes('placeholder.svg') == false) {
                        return;
                    }
                }

                // Integrations
                masonry = lazyImage.closest(".masonry");

                if (typeof lazyImage.dataset.src !== 'undefined' && typeof lazyImage.dataset.src !== undefined) {
                    lazyImage.src = lazyImage.dataset.src;
                }

                var imageSrc = lazyImage.src;
                //imageSrc = imageSrc.replace(/\.jpeg|\.jpg/g, '.webp');
                //lazyImage.src = imageSrc;

                lazyImage.classList.add("ic-fade-in");
                lazyImage.classList.remove("wps-ic-lazy-image");

                lazyImages = lazyImages.filter(function (image) {
                    return image !== lazyImage;
                });

            }
        });

        active = false;
    }
}

window.addEventListener("resize", lazyLoad);
window.addEventListener("orientationchange", lazyLoad);
document.addEventListener("scroll", lazyLoad);
document.addEventListener("DOMContentLoaded", load);