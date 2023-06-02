class AfterpayCategoryMessage {

    constructor(stripe, data) {
        this.id = 'stripe_afterpay';
        this.data = data;
        this.stripe = stripe;
        this.elements = stripe.elements({...this.data[this.id].elementOptions});
        this.initialize();
    }

    initialize() {
        this.createMessages();

    }

    createMessages() {
        if (this.isSupportedCurrency(this.data.currency)) {
            for (const product of this.data.products) {
                if (this.isSupportedProduct(product, this.data.currency)) {
                    this.createMessage(product);
                }
            }
        }

    }

    createMessage(product) {
        const element = this.elements.create('afterpayClearpayMessage', {
            ...{
                amount: product.price_cents,
                currency: this.data.currency,
                ...this.data[this.id].msgOptions
            }
        });
        const el = this.getMessageContainer(product);
        if (el) {
            element.mount(el);
        }
    }

    isSupportedCurrency(currency) {
        return this.data[this.id].supportedCurrencies.includes(currency);
    }

    isSupportedProduct(product, currency) {
        if (this.data.product_types.includes(product.product_type)) {
            const price = product.price;
            const [country, min, max] = this.data[this.id].requiredParams[currency];
            return !this.hideIneligible() || min <= price && price <= max;
        }
        return false;

    }

    getMessageContainer(product) {
        let id = `${this.id}-${product.id}`;
        id = `wc-stripe-shop-message-${id}`;
        return document.getElementById(`${id}`);
    }

    hideIneligible() {
        return this.data[this.id].hideIneligible;
    }

}

export default AfterpayCategoryMessage;