/* Import variables from sass */
import themeVars from '../styles/_theme.module.scss';

export const theme: ThemeType = {
	bps: {
		bp01: checkSassVar(themeVars.bp01),
		bp02: checkSassVar(themeVars.bp02),
		bp03: checkSassVar(themeVars.bp03),
		bp04: checkSassVar(themeVars.bp04),
	},
	colors: {
		color01: checkSassVar(themeVars.color01),
		color02: checkSassVar(themeVars.color02),
		color03: checkSassVar(themeVars.color03),
		color04: checkSassVar(themeVars.color04),
		color05: checkSassVar(themeVars.color05),
		color06: checkSassVar(themeVars.color06),
		color07: checkSassVar(themeVars.color07),
		color08: checkSassVar(themeVars.color08),
		color09: checkSassVar(themeVars.color09),
		color10: checkSassVar(themeVars.color10),
		color11: checkSassVar(themeVars.color11),
		color12: checkSassVar(themeVars.color12),
	},
};

/* Ensure an empty or undefined sass value is false */
function checkSassVar(value: string) {
	if (value) {
		if (value === 'true') {
			return true;
		} else if (value === 'false') {
			return false;
		} else {
			const valueAsNumber = Number(value);
			const formattedValue = isNaN(valueAsNumber) ? value.replace(/"/gi, "'") : valueAsNumber;
			return formattedValue;
		}
	} else {
		return false;
	}
}
