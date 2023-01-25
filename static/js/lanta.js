var isAndroid = window.navigator.userAgent.toLowerCase().includes("android");

    postLoadingStarted = function() {
        if (isAndroid) {
            Android.postLoadingStarted();
        } else {
            if (window.webkit && window.webkit.messageHandlers && window.webkit.messageHandlers.loadingStartedHandler) {
                window.webkit.messageHandlers.loadingStartedHandler.postMessage({
                    "loading": "started"
                });
            }
        }
    }

    postloadingFinished = function() {
        if (isAndroid) {
            Android.postloadingFinished();
        } else {
            if (window.webkit && window.webkit.messageHandlers && window.webkit.messageHandlers.loadingFinishedHandler) {
                window.webkit.messageHandlers.loadingFinishedHandler.postMessage({
                    "loading": "finished"
                });
            }
        }
    }

    postRefreshParent = function(timeout) {
        if (isAndroid) {
            Android.postRefreshParent(timeout);
        } else {
            if (window.webkit && window.webkit.messageHandlers && window.webkit.messageHandlers.refreshParentHandler) {
                window.webkit.messageHandlers.refreshParentHandler.postMessage({
                    "timeout": timeout
                });
            }
        }
    }

    if (isAndroid) {
        bearerToken = function() {
            return Android.bearerToken();
        }
    }
    
    if (typeof bearerToken != 'function') {
	bearerToken = function() {
	    let url = new URL(document.location.href);
            return  url.searchParams.get('token');
	}
    }