/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { coreStore } from '@ithemes/security.packages.data';
import { ModulePanelHeaderFill } from '@ithemes/security.pages.settings';

/**
 * Internal dependencies
 */
import { GetProMalwareScheduling } from '../components';

export default function App() {
	const { installType } = useSelect(
		( select ) => ( {
			installType: select( coreStore ).getInstallType(),
		} ),
		[]
	);
	return (
		<ModulePanelHeaderFill>
			{ ( { module } ) => module.id === 'malware-scheduling' &&
				installType === 'free' &&
				<GetProMalwareScheduling /> }
		</ModulePanelHeaderFill>
	);
}
