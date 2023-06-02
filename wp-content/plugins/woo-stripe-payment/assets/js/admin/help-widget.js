window.zESettings = {
    webWidget: {
        color: {
            theme: '#6772e5'
        },
        position: {horizontal: 'right', vertical: 'bottom'},
        zIndex: 9999999999,
        launcher: {
            label: {
                'en-US': 'Contact Us'
            }
        },
        contactForm: {
            title: {
                'en-US': 'Contact Payment Plugins',
            },
            fields: [
                {id: '360024242873', prefill: {'*': 'Stripe'}},
                {id: '4423709831443', prefill: {'*': JSON.stringify(wcStripeSupportParams.report, null, 2)}}
            ]
        },
        helpCenter: {
            suppress: true
        }
    }
}

window.zEmbed || function (e, t) {
    var n, o, d, i, s, a = [], r = document.createElement("iframe");
    window.zEmbed = function () {
        a.push(arguments)
    }, window.zE = window.zE || window.zEmbed, r.src = "javascript:false", r.title = "", r.role = "presentation", (r.frameElement || r).style.cssText = "display: none", d = document.getElementsByTagName("script"), d = d[d.length - 1], d.parentNode.insertBefore(r, d), i = r.contentWindow, s = i.document;
    try {
        o = s
    } catch (e) {
        n = document.domain, r.src = 'javascript:var d=document.open();d.domain="' + n + '";void(0);', o = s
    }
    o.open()._l = function () {
        var o = this.createElement("script");
        n && (this.domain = n), o.id = "js-iframe-async", o.src = e, this.t = +new Date, this.zendeskHost = t, this.zEQueue = a, this.body.appendChild(o)
    }, o.write('<body onload="document._l();">'), o.close()
}("https://assets.zendesk.com/embeddable_framework/main.js", "paymentplugins.zendesk.com");

jQuery(function ($) {
    zE(function () {
        zE.hide();
    });
    zE('webWidget', 'prefill', {
        name: {
            value: wcStripeSupportParams.name
        },
        email: {
            value: wcStripeSupportParams.email
        }
    });
    $(document.body).on('click', '#stripeSupportButton', function (e) {
        e.preventDefault();
        zE("webWidget", "updateSettings", {
            webWidget: {
                offset: {
                    horizontal: $('.wc-stripe-support__page').outerWidth() / 2 - (374 / 2) + 'px'
                }
            }
        });
        zE(function () {
            zE.activate();
        });
    })
});