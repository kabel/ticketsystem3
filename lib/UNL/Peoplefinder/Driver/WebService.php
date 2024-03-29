<?php
class UNL_Peoplefinder_Driver_WebService implements UNL_Peoplefinder_DriverInterface
{
    /**
     * The address to the webservice
     *
     * @var string
     */
    public $service_url = 'http://peoplefinder.unl.edu/service.php';

    protected $_respFormat = 'php';

    function __construct($options = array())
    {
        if (isset($options['service_url'])) {
            $this->service_url = $options['service_url'];
        }
    }

    public function resultCallback($result)
    {
        if ($result) {
            $result = unserialize($result);
        }

        return $result;
    }

    function getExactMatches($query, $affiliation = null)
    {
        $results = file_get_contents($this->service_url.'?q='.urlencode($query).'&format='.$this->_respFormat.'&affiliation='.urlencode($affiliation).'&method=getExactMatches');
        return $this->resultCallback($results);
    }

    function getAdvancedSearchMatches($query, $affliation = null)
    {
        if (empty($affiliation)) {
            $affiliation = '';
        }
        $results = file_get_contents($this->service_url.'?sn='.urlencode($query['sn']).'&cn='.urlencode($query['cn']).'&format='.$this->_respFormat.'&affiliation='.urlencode($affiliation).'&method=getAdvancedSearchMatches');
        return $this->resultCallback($results);
    }

    function getLikeMatches($query, $affiliation = null, $excluded_records = array())
    {
        $results = file_get_contents($this->service_url.'?q='.urlencode($query).'&format='.$this->_respFormat.'&affiliation='.urlencode($affiliation).'&method=getLikeMatches');
        $results = $this->resultCallback($results);

        if (count($excluded_records)) {
            foreach ($results as $i=>$record) {
                foreach($excluded_records as $e=>$exclude) {
                    if ((string)$exclude->uid == (string)$record->uid) {
                        unset($results[$i]);
                        break;
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Get matches for a phone search
     *
     * @param string $query       Numerical search query
     * @param string $affiliation eduPersonAffiliation, eg, student, staff, faculty
     *
     * @return UNL_Peoplefinder_SearchResults
     */
    function getPhoneMatches($query, $affiliation = null)
    {
        $results = file_get_contents($this->service_url.'?q='.urlencode($query).'&format='.$this->_respFormat.'&affiliation='.urlencode($affiliation).'&method=getPhoneMatches');
        return $this->resultCallback($results);
    }

    /**
     * Get an individual's record within the directory.
     *
     * @param string $uid Unique ID for the user, eg: bbieber2
     *
     * @return UNL_Peoplefinder_Record
     */
    function getUID($uid)
    {
        $record = file_get_contents($this->service_url.'?uid='.urlencode($uid).'&format='.$this->_respFormat);

        if (false === $record) {
            throw new Exception('Could not find that user!', 404);
        }

        if (!$record = $this->resultCallback($record)) {
            throw new Exception('Error retrieving the data from the web service');
        }

        return $record;
    }

    function getRoles($dn)
    {
        $url = $this->service_url.'?view=roles&format='.$this->_respFormat.'&&dn='.urlencode($dn);
        $results = file_get_contents($url);
        $results = $this->resultCallback($results);

        return new UNL_Peoplefinder_Person_Roles(array('iterator'=>new ArrayIterator($results)));
    }

    function getHRPrimaryDepartmentMatches($query, $affiliation = null)
    {
        $results = file_get_contents($this->service_url.'?q=d:'.urlencode($query).'&format='.$this->_respFormat.'&affiliation='.urlencode($affiliation).'&method=getHRPrimaryDepartmentMatches');
        return $this->resultCallback($results);
    }

    public function getHROrgUnitNumberMatches($query, $affiliation = null)
    {
        $results = file_get_contents('http://directory.unl.edu/departments/?view=deptlistings&org_unit='.urlencode($query).'&format='.$this->_respFormat);
        $results = $this->resultCallback($results);
        return new UNL_Peoplefinder_Department_Personnel($results);
    }
}
