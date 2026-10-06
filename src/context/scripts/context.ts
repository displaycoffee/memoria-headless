/* Scripts */
import type { ContextValuesType } from './context-types';
import { theme } from '../../_core/scripts/theme';
import { utils, utilsBrowser } from '../../_core/scripts/utils';

/* Global context to use throughout the theme */
export const context: ContextValuesType = {
	theme,
	utils,
	utilsBrowser,
};
