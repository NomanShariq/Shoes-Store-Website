class AffirmCategoryMessage {

    constructor(stripe, data) {
        this.id = 'stripe_affirm';
        this.stripe = stripe;
        this.elements = stripe.elements({locale: 'auto'});
        this.data = data;
        this.initialize();
    }

    initialize() {
        this.createMessages();
    }

    createMessages() {
        if (this.isSupportedCurrency(this.data.currency)) {
            for (const product of this.data.products) {
                if (this.isSupportedProduct(product.product_type)) {
                    this.createMessage(product);
                }
            }
        }
    }

    isSupportedCurrency(currency) {
        return this.data[this.id].supportedCurrencies.includes(currency);
    }

    createMessage(product) {
        const element = this.elements.create('affirmMessage', this.getMessageOptions(product));
        const el = this.getMessageContainer(product);
        if (el) {
            element.mount(el);
        }
    }

    isSupportedProduct(type) {
        return this.data.product_types.includes(type);
    }

    getMessageContainer(product) {
        let id = `${this.id}-${product.id}`;
        id = `wc-stripe-shop-message-${id}`;
        return document.getElementById(`${id}`);
    }

    getMessageOptions(product) {
        return {
            amount: product.price_cents,
            currency: this.data.currency,
            ...this.data[this.id].messageOptions
        }
    }
}

export default AffirmCategoryMessage;