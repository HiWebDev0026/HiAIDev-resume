/*
 * Sale Period Setting
 */

 /**
 * WordPress Dependencies
 */
const { __ } = wp.i18n;
const {
	addFilter
} = wp.hooks;
const {
	Fragment
}	= wp.element;
const {
	InspectorAdvancedControls
}	= wp.blockEditor;
const {
	createHigherOrderComponent
} = wp.compose;
const {
	SelectControl
} = wp.components;

const allowed_on_blocks = ['core/columns','core/cover','core/group'];

/**
 * Add sale period select controls on Advanced Block Panel.
 *
 * @param {function} BlockEdit Block edit component.
 *
 * @return {function} BlockEdit Modified block edit component.
 */
const withAdvancedControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {

		const {
			attributes,
			setAttributes,
			isSelected,
		} = props;

		const {
			sale_period_visibility,
		} = attributes;
		
		return (
			<Fragment>
				<BlockEdit {...props} />
				{ isSelected && allowed_on_blocks.includes(props.name) &&
					<InspectorAdvancedControls>
						<SelectControl
							value={ sale_period_visibility }
							help={__( 'Select the sale period this content is visible for.', 'sitewide-sales' ) }
							options={ [
								{ label: __( 'Always', 'sitewide-sales' ), value: '' },
								{ label: __( 'Before Sale', 'sitewide-sales' ), value: 'pre-sale' },
								{ label: __( 'During Sale', 'sitewide-sales' ), value: 'sale' },
								{ label: __( 'After Sale', 'sitewide-sales' ), value: 'post-sale' }
							] }
							label={ __( 'Sale Period Visibility', 'sitewide-sales' ) }
							onChange={ sale_period_visibility => setAttributes( { sale_period_visibility } ) }
						/>
					</InspectorAdvancedControls>
				}
			</Fragment>
		);
	};
}, 'withAdvancedControls');

addFilter(
	'editor.BlockEdit',
	'swsales/sale-period-setting',
	withAdvancedControls
);

/**
 * Add custom attribute for sale period visibility.
 *
 * @param {Object} settings Settings for the block.
 *
 * @return {Object} settings Modified settings.
 */
function addAttributes( settings, name ) {
	if (typeof settings.attributes !== 'undefined') {
		if (allowed_on_blocks.includes(name)) {
			settings.attributes = Object.assign( settings.attributes, {
				sale_period_visibility: { 
					type: 'string',
					default: '',
				}
			} );
		}
	}

	return settings;
}

addFilter(
	'blocks.registerBlockType',
	'swsales/sale-period-setting',
	addAttributes
);
