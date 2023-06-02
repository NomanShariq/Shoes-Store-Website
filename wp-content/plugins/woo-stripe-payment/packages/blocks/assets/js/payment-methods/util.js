import {loadStripe} from '@stripe/stripe-js';
import {getSetting} from '@woocommerce/settings'
import apiFetch from "@wordpress/api-fetch";
import {getCurrency, formatPrice as wcFormatPrice} from '@woocommerce/price-format';

const {publishableKey, stripeParams} = getSetting('stripeGeneralData');
const messages = getSetting('stripeErrorMessages');
const countryLocale = getSetting('countryLocale', {});

const SHIPPING_OPTION_REGEX = /^([\w]+)\:(.+)$/;

const routes = getSetting('stripeGeneralData').routes;

const creditCardForms = {};

const localPaymentMethods = [];

const CACHE_PREFIX = 'stripe:';

const PAYMENT_REQUEST_ADDRESS_MAPPINGS = {
    recipient: (address, name) => {
        address.first_name = name.split(' ').slice(0, -1).join(' ');
        address.last_name = name.split(' ').pop();
        return address;
    },
    payerName: (address, name) => {
        address.first_name = name.split(' ').slice(0, -1).join(' ');
        address.last_name = name.split(' ').pop();
        return address;
    },
    country: 'country',
    addressLine: (address, value) => {
        if (value[0]) {
            address.address_1 = value[0];
        }
        if (value[1]) {
            address.address_2 = value[1];
        }
        return address;
    },
    line1: 'address_1',
    line2: 'address_2',
    city: 'city',
    region: 'state',
    state: 'state',
    postalCode: 'postcode',
    postal_code: 'postcode',
    payerEmail: 'email',
    payerPhone: 'phone'
}

export const initStripe = new Promise((resolve, reject) => {
    loadStripe(publishableKey, stripeParams).then(stripe => {
        resolve(stripe);
    }).catch(err => {
        resolve({error: err});
    });
});

export const registerCreditCardForm = ({id, ...props}) => {
    creditCardForms[id] = props;
}

export const getCreditCardForm = (id) => {
    return creditCardForms.hasOwnProperty(id) ? creditCardForms[id] : {};
}

export const getRoute = (route) => {
    return routes?.[route] ? routes[route] : console.log(`${route} is not a valid route`);
}

export const ensureSuccessResponse = (responseTypes, data = {}) => {
    return {type: responseTypes.SUCCESS, ...data};
}

/**
 * Returns a formatted error object used by observers
 * @param responseTypes
 * @param error
 * @returns {{type: *, message: *}}
 */
export const ensureErrorResponse = (responseTypes, error, options = {}) => {
    return {type: responseTypes.ERROR, message: getErrorMessage(error), ...options}
};

/**
 * Return a customized error message.
 * @param error
 */
export const getErrorMessage = (error) => {
    if (typeof error == 'string') {
        return error;
    }
    if (error?.code && messages?.[error.code]) {
        return messages[error.code];
    }
    if (error?.statusCode) {
        return messages?.[error.statusCode] ? messages[error.statusCode] : error.statusMessage;
    }
    return error.message;
}

/**
 * Return a Stripe formatted billing_details object from a WC address
 * @param billingAddress
 */
export const getBillingDetailsFromAddress = (billingAddress) => {
    let billing_details = {
        name: `${billingAddress.first_name} ${billingAddress.last_name}`,
        address: {
            city: billingAddress.city || null,
            country: billingAddress.country || null,
            line1: billingAddress.address_1 || null,
            line2: billingAddress.address_2 || null,
            postal_code: billingAddress.postcode || null,
            state: billingAddress.state || null
        }
    }
    if (billingAddress?.phone) {
        billing_details.phone = billingAddress.phone;
    }
    if (billingAddress?.email) {
        billing_details.email = billingAddress.email;
    }
    return billing_details;
}

export const getSettings = (name) => (key) => {
    if (key) {
        return getSetting(name)[key];
    }
    return getSetting(name);
}

export class StripeError extends Error {
    constructor(error) {
        super(error.message);
        this.error = error;
    }
}

/**
 * Returns true if the provided value is empty.
 * @param value
 * @returns {boolean}
 */
export const isEmpty = (value) => {
    if (typeof value === 'string') {
        return value.length == 0 || value == '';
    }
    if (Array.isArray(value)) {
        return array.length == 0;
    }
    if (typeof value === 'object') {
        return Object.keys(value).length == 0;
    }
    if (typeof value === 'undefined') {
        return true;
    }
    return true;
}

export const removeNumberPrecision = (value, unit) => {
    return value / 10 ** unit;
}

/**
 *
 * @param address
 * @param country
 */
export const isAddressValid = (address, exclude = []) => {
    const fields = getLocaleFields(address.country);
    for (const [key, value] of Object.entries(address)) {
        if (!exclude.includes(key) && fields?.[key] && fields[key].required) {
            if (isEmpty(value)) {
                return false;
            }
        }
    }
    return true;
}

export const getLocaleFields = (country) => {
    let localeFields = {...countryLocale.default};
    if (country && countryLocale?.[country]) {
        localeFields = Object.entries(countryLocale[country]).reduce((locale, [key, value]) => {
            locale[key] = {...locale[key], ...value}
            return locale;
        }, localeFields);
        ['phone', 'shipping-phone', 'email'].forEach(key => {
            let node = document.getElementById(key);
            if (node) {
                localeFields[key] = {required: node.required};
            }
        });
    }
    return localeFields;
}

/**
 * Return true if the field is required by the cart
 * @param field
 * @param country
 * @returns {boolean|*}
 */
export const isFieldRequired = (field, country = false) => {
    const fields = getLocaleFields(country);
    return [field] in fields && fields[field].required;
}

export const getSelectedShippingOption = (id) => {
    const result = id.match(SHIPPING_OPTION_REGEX);
    if (result) {
        const {1: packageIdx, 2: rate} = result;
        return [rate, packageIdx];
    }
    return [];
}

export const hasShippingRates = (shippingRates) => {
    return shippingRates.map(rate => {
        return rate.shipping_rates.length > 0;
    }).filter(Boolean).length > 0;
}

/**
 * Return true if the customer is logged in.
 * @param customerId
 * @returns {boolean}
 */
export const isUserLoggedIn = (customerId) => {
    return customerId > 0;
}

const syncPaymentIntentWithOrder = async (order_id, client_secret) => {
    try {
        await apiFetch({
            url: routes['sync/intent'],
            method: 'POST',
            data: {order_id, client_secret}
        })
    } catch (error) {
        console.log(error);
    }
}

export const handleCardAction = async (
    {
        redirectUrl,
        responseTypes,
        name,
        method = 'handleCardAction',
        savePaymentMethod = false,
        data = {}
    }) => {
    try {
        let match = redirectUrl.match(/#response=(.+)/)
        if (match) {
            let {type, client_secret, order_id, order_key} = JSON.parse(window.atob(decodeURIComponent(match[1])));
            const stripe = await initStripe;
            let result;
            if (type === 'intent') {
                result = await stripe[method](client_secret);
            } else {
                result = await stripe.confirmCardSetup(client_secret);
            }
            if (result.error) {
                return ensureErrorResponse(responseTypes, result.error);
            }
            // success so finish processing order then redirect to thank you page
            data = {...data, order_id, order_key, [`${name}_save_source_key`]: savePaymentMethod};
            let response = await apiFetch({
                url: getRoute('process/payment'),
                method: 'POST',
                data
            })
            if (response.messages) {
                return ensureErrorResponse(responseTypes, response.messages);
            }
            return ensureSuccessResponse(responseTypes, {
                redirectUrl: response.redirect
            });
        } else {
            return ensureSuccessResponse(responseTypes);
        }
    } catch (err) {
        console.log(err);
        return ensureErrorResponse(responseTypes, err);
    }
}

/**
 * Convert a payment wallet address to a WC cart address.
 * @param address_mappings
 * @returns {function(*, *=): {}}
 */
export const toCartAddress = (address_mappings = PAYMENT_REQUEST_ADDRESS_MAPPINGS) => (address, args = {}) => {
    const cartAddress = {};
    address = {...address, ...filterEmptyValues(args)};
    for (let [key, cartKey] of Object.entries(address_mappings)) {
        if (address?.[key]) {
            if (typeof cartKey === 'function') {
                cartKey(cartAddress, address[key]);
            } else {
                cartAddress[cartKey] = address[key];
            }
        }
    }
    return cartAddress;
}

/**
 * Given a WC formatted address, return only the intermediate address values
 * @param address
 * @param fields
 */
export const getIntermediateAddress = (address, fields = ['city', 'postcode', 'state', 'country']) => {
    const intermediateAddress = {};
    for (let key of fields) {
        intermediateAddress[key] = address[key];
    }
    return intermediateAddress;
}

/**
 *
 * @param values
 * @returns {{}|{[p: string]: *}}
 */
export const filterEmptyValues = (values) => {
    return Object.keys(values).filter(key => Boolean(values[key])).reduce((obj, key) => ({
        ...obj,
        [key]: values[key]
    }), {});
}

export const formatPrice = (price, currencyCode) => {
    const {prefix, suffix, decimalSeparator, minorUnit, thousandSeparator} = getCurrency(currencyCode);
    if (price == '' || price === undefined) {
        return price;
    }

    price = typeof price === 'string' ? parseInt(price, 10) : price;
    price = price / 10 ** minorUnit;
    price = price.toString().replace('.', decimalSeparator);
    let fractional = '';
    const index = price.indexOf(decimalSeparator);
    if (index < 0) {
        if (minorUnit > 0) {
            price += `${decimalSeparator}${new Array(minorUnit + 1).join('0')}`;
        }
    } else {
        fractional = price.substr(index + 1);
        if (fractional.length < minorUnit) {
            price += new Array(minorUnit - fractional.length + 1).join('0');
        }
    }

    // separate out price and decimals so thousands separator can be added.
    const match = price.match(new RegExp(`(\\d+)\\${decimalSeparator}(\\d+)`));
    if (match) {
        ({1: price, 2: fractional} = match);
    }
    price = price.replace(new RegExp(`\\B(?=(\\d{3})+(?!\\d))`, 'g'), `${thousandSeparator}`);
    price = fractional?.length > 0 ? price + decimalSeparator + fractional : price;
    price = prefix + price + suffix;
    return price;
}

export const getShippingOptions = (shippingRates) => {
    let options = [];
    shippingRates.forEach((shippingPackage, idx) => {
        // sort by selected rate
        shippingPackage.shipping_rates.sort((rate) => {
            return rate.selected ? -1 : 1;
        });
        let rates = shippingPackage.shipping_rates.map(rate => {
            let txt = document.createElement('textarea');
            txt.innerHTML = rate.name;
            let price = formatPrice(rate.price, rate.currency_code);
            return {
                id: getShippingOptionId(idx, rate.rate_id),
                label: txt.value,
                //detail: `${price}`,
                amount: parseInt(rate.price, 10)
            }
        });
        options = [...options, ...rates];
    });
    return options;
}

export const getShippingOptionId = (packageId, rateId) => `${packageId}:${rateId}`

export const getDisplayItems = (cartItems, {minorUnit}) => {
    let items = [];
    const keys = ['total_tax', 'total_shipping'];
    cartItems.forEach(item => {
        if (0 < item.value || (item.key && keys.includes(item.key))) {
            items.push({
                label: item.label,
                pending: false,
                amount: item.value
            });
        }
    })
    return items;
}

const canPay = {};

export const canMakePayment = ({country, currency, total}, callback) => {
    return new Promise((resolve, reject) => {
        const key = [country, currency, total.amount].reduce((key, value) => `${key}-${value}`);
        if (!currency) {
            return resolve(false);
        }
        if (key in canPay) {
            return resolve(canPay[key]);
        }
        return initStripe.then(stripe => {
            if (stripe.error) {
                return reject(stripe.error);
            }
            const request = stripe.paymentRequest({
                country,
                currency,
                total
            });
            request.canMakePayment().then(result => {
                canPay[key] = callback(result);
                return resolve(canPay[key]);
            });
        }).catch(reject);
    });
};

export const registerLocalPaymentMethod = (paymentMethod) => {
    localPaymentMethods.push(paymentMethod);
}

export const getLocalPaymentMethods = () => localPaymentMethods;

export const cartContainsPreOrder = () => {
    const data = getSetting('stripePaymentData');
    return data && data.pre_order;
}

export const cartContainsSubscription = () => {
    const data = getSetting('stripePaymentData');
    return data && data.subscription;
}

export const getDefaultSourceArgs = ({type, amount, billingData, currency, returnUrl}) => {
    return {
        type,
        amount,
        currency,
        owner: getBillingDetailsFromAddress(billingData),
        redirect: {
            return_url: returnUrl
        }
    }
}

export const isTestMode = () => {
    return getSetting('stripeGeneralData').mode === 'test';
}

const getCacheKey = (key) => `${CACHE_PREFIX}${key}`;

export const storeInCache = (key, value) => {
    const exp = Math.floor(new Date().getTime() / 1000) + (60 * 15);
    if ('sessionStorage' in window) {
        sessionStorage.setItem(getCacheKey(key), JSON.stringify({value, exp}));
    }
}

export const getFromCache = (key) => {
    if ('sessionStorage' in window) {
        try {
            const item = JSON.parse(sessionStorage.getItem(getCacheKey(key)));
            if (item) {
                const {value, exp} = item;
                if (Math.floor(new Date().getTime() / 1000) > exp) {
                    deleteFromCache(getCacheKey(key));
                } else {
                    return value;
                }
            }
        } catch (err) {
        }
    }
    return null;
}

export const deleteFromCache = (key) => {
    if ('sessionStorage' in window) {
        sessionStorage.removeItem(getCacheKey(key));
    }
}

export const versionCompare = (ver1, ver2, compare) => {
    switch (compare) {
        case '<':
            return ver1 < ver2;
        case '>':
            return ver1 > ver2;
        case '<=':
            return ver1 <= ver2;
        case '>=':
            return ver1 >= ver2;
        case '=':
            return ver1 == ver2;
    }
    return false;
}

export const isCartPage = () => getSetting('stripeGeneralData').page === 'cart';

export const isCheckoutPage = () => getSetting('stripeGeneralData').page === 'checkout';

export const isNextActionRequired = (url) => {
    let match = url.match(/#response=(.+)/);
    let args = null;
    if (match) {
        args = JSON.parse(window.atob(decodeURIComponent(match[1])));
    }
    return args;
}

export const DEFAULT_SHIPPING_ADDRESS = {
    'first_name': '',
    'last_name': '',
    'company': '',
    'address_1': '',
    'address_2': '',
    'city': '',
    'state': '',
    'postcode': '',
    'country': '',
    'phone': '',
}

export const DEFAULT_BILLING_ADDRESS = {
    ...DEFAULT_SHIPPING_ADDRESS,
    'email': ''
}