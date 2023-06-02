import './style.scss';

export const PaymentMethodLabel = ({title, icons, paymentMethod, ...props}) => {
    const {PaymentMethodLabel: Label, PaymentMethodIcons: Icons} = props.components;
    if (!Array.isArray(icons)) {
        icons = [icons];
    }
    return (
        <span className={`wc-stripe-label-container ${paymentMethod}`}>
            <Label text={title}/>
            <Icons icons={icons} align='left'/>
        </span>
    )
}