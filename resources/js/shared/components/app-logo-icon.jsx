export default function AppLogoIcon(props) {
    return (<img {...props} src="/images/ficct-logo-icon.png" alt="Logo FICCT" className={`object-contain ${props.className ?? ''}`}/>);
}
