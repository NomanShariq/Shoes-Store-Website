import {getShippingOptionId, removeNumberPrecision, toCartAddress as mapAddressToCartAddress} from "../util";
import {formatPrice} from '../util';
import {getSetting} from '@woocommerce/settings'

const generalData = getSetting('stripeGeneralData');

const ADDRESS_MAPPINGS = {
    name: (address, name) => {
        address.first_name = name.split(' ').slice(0, -1).join(' ');
        address.last_name = name.split(' ').pop();
        return address;
    },
    countryCode: 'country',
    address1: 'address_1',
    address2: 'address_2',
    locality: 'city',
    administrativeArea: 'state',
    postalCode: 'postcode',
    email: 'email',
    phoneNumber: 'phone'
}

export const getTransactionInfo = ({billing, processingCountry, totalPriceLabel}, status = 'ESTIMATED') => {
    const {cartTotal, cartTotalItems, currency} = billing;
    const transactionInfo = {
        countryCode: processingCountry,
        currencyCode: currency.code,
        totalPriceStatus: status,
        totalPrice: removeNumberPrecision(cartTotal.value, currency.minorUnit).toString(),
        displayItems: getDisplayItems(cartTotalItems, currency.minorUnit),
        totalPriceLabel
    }
    return transactionInfo;
}

export const getPaymentRequestUpdate = ({billing, shippingData, processingCountry, totalPriceLabel}) => {
    const {needsShipping, shippingRates} = shippingData;
    let update = {
        newTransactionInfo: getTransactionInfo({
            billing, processingCountry, totalPriceLabel
        }, 'FINAL')
    }
    if (needsShipping) {
        update.newShippingOptionParameters = getShippingOptionParameters(shippingRates);
    }
    return update;
}

/**
 * Return an array of line item objects
 * @param cartTotalItems
 * @param unit
 * @returns {[]}
 */
const getDisplayItems = (cartTotalItems, unit = 2) => {
    let items = [];
    const keys = ['total_tax', 'total_shipping'];
    cartTotalItems.forEach(item => {
        if (0 < item.value || (item.key && keys.includes(item.key))) {
            items.push({
                label: item.label,
                type: 'LINE_ITEM',
                price: removeNumberPrecision(item.value, unit).toString()
            });
        }
    })
    return items;
}

export const getShippingOptionParameters = (shippingRates) => {
    const shippingOptions = getShippingOptions(shippingRates);
    const shippingOptionIds = shippingOptions.map(option => option.id);
    let defaultSelectedOptionId = shippingOptionIds.slice(0, 1).shift();
    shippingRates.forEach((shippingPackage, idx) => {
        shippingPackage.shipping_rates.forEach(rate => {
            if (rate.selected) {
                defaultSelectedOptionId = getShippingOptionId(idx, rate.rate_id);
            }
        });
    });
    return {
        shippingOptions,
        defaultSelectedOptionId,
    }
}

//id label description
export const getShippingOptions = (shippingRates) => {
    let options = [];
    shippingRates.forEach((shippingPackage, idx) => {
        let rates = shippingPackage.shipping_rates.map(rate => {
            let txt = document.createElement('textarea');
            txt.innerHTML = rate.name;
            let price = formatPrice(rate.price, rate.currency_code);
            return {
                id: getShippingOptionId(idx, rate.rate_id),
                label: txt.value,
                description: `${price}`
            }
        });
        options = [...options, ...rates];
    });
    return options;
}

export const toCartAddress = mapAddressToCartAddress(ADDRESS_MAPPINGS);
