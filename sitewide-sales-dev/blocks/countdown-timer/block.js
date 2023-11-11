/**
 * Block: Countdown Timer
 *
 *
 */

/**
 * Internal block libraries
 */
const { __ } = wp.i18n;
const {
	registerBlockType
} = wp.blocks;
const {
	PanelBody,
	SelectControl,
} = wp.components;
const {
	InspectorControls,
	useBlockProps,
} = wp.blockEditor;

/**
 * Register block
 */
export default registerBlockType(
	'swsales/countdown-timer',
	{
		apiVersion: 2,
		title: __( 'Countdown Timer', 'sitewide-sales' ),
		description: __( 'Add a countdown timer on your Sitewide Sale landing page that counts down to your sale start date or end date.', 'sitewide-sales' ),
		category: 'swsales',
		icon: {
			background: '#FFFFFF',
			foreground: '#1A688B',
			src: 'clock',
		},
		keywords: [
			__( 'sale countdown', 'sitewide-sales' ),
			__( 'sale timer', 'sitewide-sales' ),
		],
		attributes: {
			end_on: {
				type: 'string',
				default: '',
			},
			anchor: {
				type: 'string',
			}
		},
		supports: {
			color: {
				background: true,
				gradients: true,
				text: true,
			},
			anchor: true
		},
		edit: ( props ) => {
			const {
				attributes: { end_on },
				setAttributes,
				className,
				isSelected,
			} = props;
			const blockProps = useBlockProps( {
				className: 'swsales_countdown_timer'
			} );
			const onChangeEndOn = ( end_on ) => {
				setAttributes( { end_on: end_on } );
			};
			return [
				isSelected && <InspectorControls>
					<PanelBody>
						<p><strong>{ __( 'Timer Ends On', 'sitewide-sales' ) }</strong></p>
						<SelectControl
							value={ end_on }
							help={__( 'Select whether this timer counts down to the sale start date or end date.', 'sitewide-sales' ) }
							options={ [
								{ label: __( 'Sale End Date', 'sitewide-sales' ), value: 'end_date' },
								{ label: __( 'Sale Start Date', 'sitewide-sales' ), value: 'start_date' },
							] }
							onChange={ onChangeEndOn }
						/>
					</PanelBody>
				</InspectorControls>,
				<div { ...blockProps }>
					<div className="swsales_countdown_timer_element">
						<div className="swsales_countdown_timer_inner">
							<span className="swsalesDays">{ '15' }</span>
							<div className="swsales_countdown_timer_period">{ __( 'Days', 'sitewide-sales' ) }</div>
						</div>
					</div>
					<div className="swsales_countdown_timer_element">
						<div className="swsales_countdown_timer_inner">
							<span className="swsalesHours">{ '11' }</span>
							<div className="swsales_countdown_timer_period">{ __( 'Hours', 'sitewide-sales' ) }</div>
						</div>
					</div>
					<div className="swsales_countdown_timer_element">
						<div className="swsales_countdown_timer_inner">
							<span className="swsalesMinutes">{ '50' }</span>
							<div className="swsales_countdown_timer_period">{ __( 'Minutes', 'sitewide-sales' ) }</div>
						</div>
					</div>
					<div className="swsales_countdown_timer_element">
						<div className="swsales_countdown_timer_inner">
							<span className="swsalesSeconds">{ '30' }</span>
							<div className="swsales_countdown_timer_period">{ __( 'Seconds', 'sitewide-sales' ) }</div>
						</div>
					</div>
				</div>,
			];
		},
		save: ( props ) => {
			const blockProps = useBlockProps.save();
			return (
				<div {...blockProps}></div>
			);
		},
	}
);
