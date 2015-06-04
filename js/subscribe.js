/*
 * SendCloud Subscribe plugin
 * http://xuhong.github.io/subscribe
 *
 * copyright 2015, xuhong chen,
 * MIT license: http://xuhong.mit-license.org
 */

'use strict';
(function(root, factory) {
    if (typeof define === 'function' && typeof define.amd === 'object') {
        define(factory)
    } else if (typeof exports === 'object') {
        module.exports = factory()
    } else {
        root.sendcloud = factory()
    }
})(this, function() {
    'use strict';

    var sendcloud = window.sendcloud = window.sendcloud || {}
    
    var head = document.head

    var htmlObj = {
        pop: '<div class="sendcloud-subscribe sendcloud-pop-subscribe"><form class="subscribe-pop-form subscribe-form"><div class="subscribe-title"></div><div class="subscribe-input-control"><input type="email" class="subscribe-email-input" placeholder="Email"></input><span class="subscribe-msg"></span></div><button class="subscribe-submit-btn" type="submit">订阅</button><div class="subscribe-success"><span class="subscribe-success-msg"></span></div></form><span class="close">x</span></div>',
        banner: '<div class="sendcloud-subscribe sendcloud-banner-subscribe"><form class="subscribe-banner-form subscribe-form"><div class="subscribe-title"></div><div class="subscribe-input-control"><input type="email" class="subscribe-email-input" placeholder="Email"></input><span class="subscribe-msg"></span></div><button class="subscribe-submit-btn" type="submit">订阅</button><div class="subscribe-success"><span class="subscribe-success-msg"></span></div></form><span class="close">x</span></div>',
        bottom: '<div class="sendcloud-subscribe sendcloud-bottom-subscribe"><form class="subscribe-bottom-form subscribe-form"><div class="subscribe-title"></div><div class="subscribe-input-control"><input type="email" class="subscribe-email-input" placeholder="Email"></input><span class="subscribe-msg"></span></div><button class="subscribe-submit-btn" type="submit">订阅</button><div class="subscribe-success"><span class="subscribe-success-msg"></span></div></form><span class="close">x</span></div>',
        embed: '<div class="sendcloud-subscribe sendcloud-embed-subscribe"><form class="subscribe-embed-form subscribe-form"><div class="subscribe-title"></div><div class="subscribe-input-control"><input type="email" class="subscribe-email-input" placeholder="Email"></input><span class="subscribe-msg"></span></div><button class="subscribe-submit-btn" type="submit">订阅</button><div class="subscribe-success"><span class="subscribe-success-msg"></span></div></form><span class="close">x</span></div>'
    }

    var defaultOpt = {
        invitecode: 'your invitecode',
        type: '',
        expires: '86400000',
        trigger: 'scroll',
        title: '邮件订阅我们吧！',
        successMsg: '订阅成功！',
        showClass: {
            banner: 'fadeInDown',
            bottom: 'fadeInRight',
            pop: 'bounceIn',
            embed: 'shake'
        },
        hiddenClass: {
            banner: 'fadeOutUp',
            bottom: 'fadeOutRight',
            pop: 'bounceOut',
            embed: 'fadeOut'
        },
        url: 'http://sendcloud.sohu.com/subInvite/subscription.do'
    }

    var userOpt,
        sc_sub,
        sc_form,
        sc_ipt,
        sc_btn,
        sc_close

    sendcloud.subscribe = function(opt) {
        if (!opt.type) return
        if (document.cookie.indexOf('sendcloud_subscribe') != '-1') return
        userOpt = extend(defaultOpt, opt)
        insertStyle(userOpt.type)
        window.onload = appendSubscribe(userOpt.type, userOpt.title)
        attachEventHandler()
        trigger(userOpt.trigger)
    }

    function trigger(type) {
        if (type === 'load') {
            window.onload = function() {
                sc_sub.classList.add('animated', userOpt.showClass[userOpt.type])
                sc_sub.style.display = 'block'
            }
        } else if (type === 'scroll') {
            window.onscroll = function() {
                sc_sub.classList.add('animated', userOpt.showClass[userOpt.type])
                sc_sub.style.display = 'block'
            }
        } else {
            var ele = document.querySelector(type)
            ele.addEventListener('click', function(e) {
                e.preventDefault()
                sc_sub.classList.remove(userOpt.hiddenClass[userOpt.type])
                sc_sub.classList.add('animated', userOpt.showClass[userOpt.type])
                sc_sub.style.display = 'block'
            })
        }
    }

    function appendSubscribe(type, title) {
    	var doc_body = document.body
        var el = sc_sub = document.createElement('div')
        el.id = 'sendcloud-subscribe-wrapper'
        el.className = 'sendcloud-' + type + '-wrapper'
        el.innerHTML = htmlObj[type]
        el.querySelector('.subscribe-title').innerHTML = title
        if (type === "embed") {
            document.querySelector('#sendcloud-embed-subscribe').appendChild(el)
        } else {
	        doc_body.appendChild(el)
        }
    }

    function insertStyle(type) {
        var styArr = ['http://sendcloud.sohu.com/css/subscribe.css', 'http://sendcloud.sohu.com/css/animate.css']
        for (var i = 0; i < styArr.length; i++) {
            var link = document.createElement('link')
            link.setAttribute('rel', 'stylesheet')
            link.href = styArr[i]
            head.appendChild(link)
        }
    }

    function attachEventHandler() {
        sc_form = sc_sub.querySelector('.subscribe-form')
        sc_ipt = sc_sub.querySelector('.subscribe-email-input')
        sc_btn = sc_sub.querySelector('.subscribe-submit-btn')
        sc_close = sc_sub.querySelector('.close')

        var isValidEmail

        sc_ipt.addEventListener('blur', function(e) {
            e.preventDefault()
            isValidEmail = validateEmail(this.value)
            if (!isValidEmail) showInvalidMsg('邮件格式不正确')
            else showInvalidMsg('')
        })

        sc_btn.addEventListener('click', function(e) {
            e.preventDefault()
            isValidEmail = validateEmail(sc_ipt.value)
            if (!isValidEmail) return
            ajaxSubscribe(sc_ipt.value)
            this.setAttribute('disabled', 'true')
        })

        sc_close.addEventListener('click', hideSubscribe)
    }

    function ajaxSubscribe(email) {
        var xhr = new XMLHttpRequest()
        xhr.onload = function() {
            sc_btn.removeAttribute('disabled')
            var res = JSON.parse(xhr.responseText)
            if (res.success) {
                showSuccessMsg()
            } else {
                // showSuccessMsg()
                showInvalidMsg(res.message)
            }
        }
        xhr.onerror = function() {
            showSuccessMsg()
        }
        xhr.open('POST', userOpt.url + '?invitecode=' + userOpt.invitecode + '&email=' + email)
        xhr.send()
    }

    function showInvalidMsg(error) {
        var msg = sc_sub.querySelector('.subscribe-msg')
        msg.innerText = error
    }

    function showSuccessMsg() {
        sc_sub.querySelector('.subscribe-success').style.display = 'block'
        sc_sub.querySelector('.subscribe-success-msg').innerText = userOpt.successMsg
        setTimeout(hideSubscribe, 1000)
    }

    function hideSubscribe() {
        sc_sub.classList.remove(userOpt.showClass[userOpt.type])
        sc_sub.classList.add(userOpt.hiddenClass[userOpt.type])
        setTimeout(function() {
            userOpt.trigger == 'load' || userOpt.trigger == 'scroll'? sc_sub.remove(): null
            sc_sub.style.display = 'none'
        }, 1000)
        document.cookie = 'sendcloud_subscribe=hidden;path=/;expires=' + new Date(Date.now() + parseInt(userOpt.expires)).toUTCString()
    }

    function validateEmail(email) {
        var re = /^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i;
        return re.test(email);
    }

    function extend(des, src) {
        for (var k in src) {
            if (src[k] && src[k].constructor && src[k].constructor === 'object') {
                des[k] = des[k] || {}
                anguments.callee(des[k], src[k])
            } else {
                des[k] = src[k]
            }
        }
        return des
    }

    return sendcloud
})
