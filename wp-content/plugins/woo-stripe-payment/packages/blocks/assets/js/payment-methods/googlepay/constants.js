export const BASE_PAYMENT_METHOD = {
    type: 'CARD',
    parameters: {
        allowedAuthMethods: ["PAN_ONLY"],
        allowedCardNetworks: ["AMEX", "DISCOVER", "INTERAC", "JCB", "MASTERCARD", "VISA"],
        assuranceDetailsRequired: true
    }
};

export const BASE_PAYMENT_REQUEST = {
    apiVersion: 2,
    apiVersionMinor: 0
}