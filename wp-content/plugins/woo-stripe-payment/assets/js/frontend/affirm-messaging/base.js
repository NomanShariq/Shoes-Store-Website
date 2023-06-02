class AffirmBaseMessage {

    constructor(gateway) {
        this.elementType = 'affirmMessage';
        this.gateway = gateway;
        this.msgElement = null;
    }

    isSupported() {
        const currency = this.gateway?.get_gateway_data()?.currency;
        return this.gateway?.params?.supportedCurrencies?.includes(currency);
    }

    createMessage() {
        if (this.isSupported() && this.createMessageElement()) {
            const el = this.getElementContainer();
            if (el) {
                this.mount(el);
            }
        }
    }

    createMessageElement() {
        if (this.msgElement) {
            this.msgElement.update(this.getMessageOptions());
        } else {
            this.msgElement = this.gateway?.elements?.create(this.elementType, this.getMessageOptions());
        }
        return this.msgElement;
    }

    getMessageOptions() {
        return {
            amount: this.getTotalPriceCents(),
            currency: this.gateway.get_currency(),
            ...this.gateway.params.messageOptions
        }
    }

    mount(el) {
        try {
            this.msgElement.mount(el);
        } catch (error) {
            console.log(error);
        }

    }

    getElementContainer() {

    }

    getTotalPriceCents() {
        return this.gateway.get_gateway_data()?.total_cents;
    }
}

export default AffirmBaseMessage;