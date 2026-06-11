export default function AppLogoIcon(props) {
    return (<img {...props} src="/images/ficct-shield-oval.png" alt="Logo FICCT" className={`object-contain ${props.className ?? ''}`}/>);
}
