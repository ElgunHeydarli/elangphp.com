import { BackendAccount } from '../../components/Workspace';
import { accountUrl } from '../../lib/backend';
export const dynamic = 'force-dynamic';
export default function AccountPage() { return <BackendAccount accountUrl={accountUrl()} />; }
