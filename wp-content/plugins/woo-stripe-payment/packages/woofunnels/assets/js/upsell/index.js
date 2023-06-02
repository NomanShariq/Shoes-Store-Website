import {loadStripe} from '@stripe/stripe-js';
import $ from 'jquery';

let stripe;

let data = {};

const getStripeParams = () => {
    if (getData('account')) {
        return {stripeAccount: getData('account')};
    }
    return {};
}

const initialize = () => {
    $(document).on('wfocu_external', onHandleSubmit);
    window.addEventListener('hashchange', handleHashChange);
    wfocuCommons.addFilter('wfocu_front_charge_data', addChargeData);
    loadStripe(getData('publishableKey'), getStripeParams()).then((client) => {
        stripe = client;
    }).catch(error => {
        console.log(error);
    });
}

const onBucketCreated = (e, bucket) => {
    data = window?.wfocu_vars?.stripeData;
    setData('bucket', bucket);
    initialize();
}

const onHandleSubmit = (e, bucket) => {
    setData('bucket', bucket);
}

const handleHashChange = (e) => {
    var match = e.newURL.match(/response=(.*)/);
    if (match) {
        const obj = JSON.parse(window.atob(decodeURIComponent(match[1])));
        getData('bucket')?.swal?.hide();
        setData('paymentIntent', obj.payment_intent);
        history.pushState({}, '', window.location.pathname + window.location.search);
        stripe.confirmCardPayment(obj.client_secret).then(response => {
            if (response.error) {
                // display message
                resetPaymentProcess();
            } else {
                setData('paymentComplete', true);
                getData('bucket').sendBucket();
            }
        }).catch(error => {
            console.log(error);
        });
    }
}

const addChargeData = (e) => {
    e['_payment_intent'] = getData('paymentIntent');
    return e;
}

const getData = (key, defaultValue = null) => {
    if (!data.hasOwnProperty(key)) {
        data[key] = defaultValue;
    }
    return data[key];
}

const setData = (key, value) => {
    data[key] = value;
}

const resetPaymentProcess = () => {
    getData('bucket').inOfferTransaction = false;
    getData('bucket').EnableButtonState();
    getData('bucket').HasEventRunning = false;
}

$(document).on('wfocuBucketCreated', onBucketCreated);