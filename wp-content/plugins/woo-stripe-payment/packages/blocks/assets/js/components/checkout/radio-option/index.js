import classnames from 'classnames';

export const RadioControlOption = ({checked, onChange, value, label}) => {
    return (
        <label
            className={classnames('wc-stripe-blocks-radio-control__option', {
                'wc-stripe-blocks-radio-control__option-checked': checked
            })}>
            <input
                className='wc-stripe-blocks-radio-control__input'
                type='radio'
                value={value}
                checked={checked}
                onChange={(event) => onChange(event.target.value)}/>
            <div className='wc-stripe-blocks-radio-control__label'>
                <span>{label}</span>
            </div>
        </label>
    )
}

export default RadioControlOption;