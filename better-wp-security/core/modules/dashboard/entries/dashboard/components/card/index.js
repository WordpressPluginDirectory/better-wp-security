/**
 * External dependencies
 */
import { ErrorBoundary } from 'react-error-boundary';
import classnames from 'classnames';
import styled from '@emotion/styled';

/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
import { memo, forwardRef } from '@wordpress/element';

/**
 * iThemes dependencies
 */
import { Surface } from '@ithemes/ui';

/**
 * Internal dependencies
 */
import { withProps } from '@ithemes/security-hocs';
import { useCardElementQueries, useCardRenderer } from '../../cards';
import CardUnknown from '../empty-states/card-unknown';
import CardCrash from '../empty-states/card-crash';
import './style.scss';

const StyledCard = styled( Surface )`
	width: 100%;
	height: 100%;
	border-radius: 2px;
	box-shadow: 0 0 5px rgba(211, 211, 211, 0.35);
`;

function UnforwardedCard( { id, dashboardId, className, gridWidth, children, ...rest }, ref ) {
	const { card, config } = useSelect(
		( select ) => ( {
			card: select( 'ithemes-security/dashboard' ).getDashboardCard( id ),
			config:
				select( 'ithemes-security/dashboard' ).getDashboardCardConfig(
					id
				) || {},
		} ),
		[ id ]
	);
	const CardRender = useCardRenderer( config );
	const eqProps = useCardElementQueries( config, rest.style, gridWidth );

	if ( card.card === 'unknown' ) {
		return (
			<StyledCard
				as="article"
				className={ classnames(
					className,
					'itsec-card',
					'itsec-card--unknown'
				) }
				ref={ ref }
				{ ...rest }
			>
				<CardUnknown card={ card } dashboardId={ dashboardId } />
			</StyledCard>
		);
	}

	if ( ! CardRender ) {
		return (
			<StyledCard
				as="article"
				className={ classnames(
					className,
					'itsec-card',
					'itsec-card--no-rendered'
				) }
				ref={ ref }
				{ ...rest }
			>
				<CardCrash card={ card } config={ config } />
			</StyledCard>
		);
	}

	return (
		<StyledCard
			as="article"
			className={ classnames( className, 'itsec-card' ) }
			id={ `itsec-card-${ card.id }` }
			ref={ ref }
			{ ...rest }
			{ ...eqProps }
		>
			<ErrorBoundary
				FallbackComponent={ withProps( { card, config } )( CardCrash ) }
			>
				<CardRender
					card={ card }
					config={ config }
					dashboardId={ dashboardId }
					eqProps={ eqProps }
				/>
			</ErrorBoundary>
			{ children }
		</StyledCard>
	);
}

const Card = forwardRef( UnforwardedCard );

export default memo( Card );
