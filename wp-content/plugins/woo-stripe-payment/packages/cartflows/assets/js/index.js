import $ from 'jquery';
import {loadStripe} from '@stripe/stripe-js';
import apiFetch from "@wordpress/api-fetch";

const data = cartflows_offer.stripeData;
const getStripe = new Promise(resolve => {
    loadStripe(data.key, (() => data.accountId ? {stripeAccount: data.accountId} : {})()).then(stripe => {
        resolve(stripe);
    }).catch(error => {
        resolve(false);
    })
});

let currentButton;

const initialize = () => {
    window.addEventListener('hashchange', handleHashChange);
    $(document.body).on('click', 'a[href*="wcf-up-offer"], a[href*="wcf-down-offer"]', handleButtonClick);
}

const handleButtonClick = (e) => {
    currentButton = $(e.currentTarget);
}

const handleHashChange = async (e) => {
    var match = e.newURL.match(/response=(.*)/);
    if (match) {
        try {
            var obj = JSON.parse(window.atob(decodeURIComponent(match[1])));
            if (obj && obj.hasOwnProperty('client_secret')) {
                history.pushState({}, '', window.location.pathname + window.location.search);
                handleCardAction(obj);
            }
        } catch (err) {

        }
    }
    return true;
}

const handleCardAction = async ({client_secret, ...props}) => {
    const stripe = await getStripe;
    stripe.handleCardAction(client_secret).then(result => {
        if (result.error) {
            $('body').trigger('wcf-update-msg', [result.error.message, 'wcf-payment-error']);
            setTimeout(() => {
                $(document.body).trigger('wcf-hide-loader')
                $(document.body).trigger('wcf-update-msg', [data.msg, 'wcf-payment-success']);
            }, data.timeout);
            syncPaymentIntent({client_secret, ...props});
        } else {
            triggerOfferClick();
        }
    })
}

const triggerOfferClick = () => {
    currentButton.click();
}

const syncPaymentIntent = (data) => {
    return new Promise((resolve, reject) => {
        apiFetch({
            path: '/wc-stripe/v1/cartflows/payment-intent',
            method: 'POST',
            data
        }).then(response => {
        }).catch(err => {
        });
    });
}

initialize();