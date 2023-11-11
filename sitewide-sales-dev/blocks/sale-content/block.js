/**
 * Block: Sale Content
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
	InnerBlocks,
} = wp.blockEditor;

/**
 * Register block
 */
export default registerBlockType(
	'swsales/sale-content',
	{
		title: __( 'Sale Content', 'sitewide-sales' ),
		description: __( 'Build your Sitewide Sale landing page with blocks. Place blocks within this section to conditionally show the content before, during, or after the sale.', 'sitewide-sales' ),
		category: 'swsales',
		icon: {
			background: '#FFFFFF',
			foreground: '#1A688B',
			src: 'visibility',
		},
		keywords: [
			__( 'sale visibility', 'sitewide-sales' ),
			__( 'before sale', 'sitewide-sales' ),
			__( 'after sale', 'sitewide-sales' ),
			__( 'sale content', 'sitewide-sales' ),
		],
		attributes: {
			period: {
				type: 'string',
				default: '',
			},
		},
		supports: {
			anchor: true
		},
		edit: props => {
			const { attributes: {period}, setAttributes, isSelected } = props;

			return [
				isSelected && <InspectorControls>
					<PanelBody>
						<p><strong>{ __( 'Sale Period', 'sitewide-sales' ) }</strong></p>
						<SelectControl
							value={ period }
							help={__( 'Select the sale period this content is visible for.', 'sitewide-sales' ) }
							options={ [
								{ label: __( 'Always', 'sitewide-sales' ), value: '' },
								{ label: __( 'Before Sale', 'sitewide-sales' ), value: 'pre-sale' },
								{ label: __( 'During Sale', 'sitewide-sales' ), value: 'sale' },
								{ label: __( 'After Sale', 'sitewide-sales' ), value: 'post-sale' },
							] }
							 onChange={ period => setAttributes( { period } ) }
						/>
					</PanelBody>
				</InspectorControls>,
				isSelected && <div className="swsales-wrapper-block" >
				<span className="swsales-block-title">{ __( 'Sitewide Sale Content', 'sitewide-sales' ) }</span>
				<InnerBlocks
					templateLock={ false }
				/>
				</div>,
				! isSelected && <div className="swsales-wrapper-block" >
				<span className="swsales-block-title">{ __( 'Sitewide Sale Content', 'sitewide-sales' ) }</span>
				<InnerBlocks
					templateLock={ false }
				/>
				</div>,
			];
		},
		save: props => {
			const {  className } = props;
				return (
					<div className={ className }>
						<InnerBlocks.Content />
					</div>
				);
			},
		}
 );
