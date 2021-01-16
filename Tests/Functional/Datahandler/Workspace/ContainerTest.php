<?php

declare(strict_types=1);
namespace B13\Container\Tests\Functional\Datahandler\Workspace;

/*
 * This file is part of TYPO3 CMS-based extension "container" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use B13\Container\Tests\Functional\Datahandler\DatahandlerTest;

class ContainerTest extends DatahandlerTest
{

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \TYPO3\TestingFramework\Core\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->importDataSet(ORIGINAL_ROOT . 'typo3conf/ext/container/Tests/Functional/Fixtures/pages.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3conf/ext/container/Tests/Functional/Fixtures/tt_content_default_language.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3conf/ext/container/Tests/Functional/Fixtures/tt_content_default_language_second_container.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3conf/ext/container/Tests/Functional/Fixtures/Workspace/sys_workspace.xml');
        $this->backendUser->setWorkspace(1);
    }

    /**
     * @test
     */
    public function newVersionDoesNotCreateNewVersionsOfChildren(): void
    {
        $datamap = [
            'tt_content' => [
                1 => [
                    'header' => 'container-ws',
                ]
            ]
        ];

        $this->dataHandler->start($datamap, [], $this->backendUser);
        $this->dataHandler->process_datamap();

        // new container
        $row = $this->fetchOneRecord('t3ver_oid', 1);
        self::assertSame(1, $row['t3ver_wsid']);
        // child
        $queryBuilder = $this->getQueryBuilder();
        $row = $queryBuilder->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    't3ver_oid',
                    $queryBuilder->createNamedParameter(2, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();
        self::assertFalse($row);
    }

    /**
     * @test
     */
    public function moveChildsColPosInContainer(): void
    {
        $cmdmap = [
            'tt_content' => [
                2 => [
                    'move' => [
                        'action' => 'paste',
                        'target' => 1,
                        'update' => [
                            'colPos' => '1-201',
                            'sys_language_uid' => 0
                        ]
                    ]
                ]
            ]
        ];
        $this->dataHandler->start([], $cmdmap, $this->backendUser);
        $this->dataHandler->process_cmdmap();

        // moved record is not modified
        $row = $this->fetchOneRecord('uid', 2);
        self::assertSame(1, $row['tx_container_parent']);
        self::assertSame(200, $row['colPos']);

        $queryBuilder = $this->getQueryBuilder();
        $rows = $queryBuilder->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    't3_origuid',
                    $queryBuilder->createNamedParameter(2, \PDO::PARAM_INT)
                )
            )
            ->orWhere(
                $queryBuilder->expr()->eq(
                    't3ver_move_id',
                    $queryBuilder->createNamedParameter(2, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchAll();
        self::assertSame(2, count($rows));
        foreach ($rows as $row) {
            self::assertSame(1, $row['pid']);
            self::assertSame(1, $row['t3ver_wsid']);
            self::assertSame(1, $row['tx_container_parent']);
            self::assertSame(201, $row['colPos']);
        }
    }

    /**
     * @test
     */
    public function moveChildOutsideContainer(): void
    {
        $cmdmap = [
            'tt_content' => [
                2 => [
                    'move' => [
                        'action' => 'paste',
                        'target' => 3,
                        'update' => [
                            'colPos' => 0,
                            'sys_language_uid' => 0

                        ]
                    ]
                ]
            ]
        ];
        $this->dataHandler->start([], $cmdmap, $this->backendUser);
        $this->dataHandler->process_cmdmap();

        // moved record is not modified
        $row = $this->fetchOneRecord('uid', 2);
        self::assertSame(1, $row['tx_container_parent']);
        self::assertSame(200, $row['colPos']);

        $queryBuilder = $this->getQueryBuilder();
        $rows = $queryBuilder->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    't3_origuid',
                    $queryBuilder->createNamedParameter(2, \PDO::PARAM_INT)
                )
            )
            ->orWhere(
                $queryBuilder->expr()->eq(
                    't3ver_move_id',
                    $queryBuilder->createNamedParameter(2, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchAll();
        self::assertSame(2, count($rows));
        foreach ($rows as $row) {
            self::assertSame(1, $row['t3ver_wsid']);
            self::assertSame(0, $row['tx_container_parent']);
            self::assertSame(0, $row['colPos']);
            self::assertSame(3, $row['pid']);
        }
    }

    /**
     * @test
     */
    public function copyChildsColPosInContainer(): void
    {
        $cmdmap = [
            'tt_content' => [
                2 => [
                    'copy' => [
                        'action' => 'paste',
                        'target' => 1,
                        'update' => [
                            'colPos' => '1-201',
                            'sys_language_uid' => 0

                        ]
                    ]
                ]
            ]
        ];
        $this->dataHandler->start([], $cmdmap, $this->backendUser);
        $this->dataHandler->process_cmdmap();

        // moved record is not modified
        $row = $this->fetchOneRecord('uid', 2);
        self::assertSame(1, $row['tx_container_parent']);
        self::assertSame(200, $row['colPos']);

        $queryBuilder = $this->getQueryBuilder();
        $rows = $queryBuilder->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    't3_origuid',
                    $queryBuilder->createNamedParameter(2, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchAll();
        self::assertSame(2, count($rows));
        foreach ($rows as $row) {
            self::assertSame(1, $row['pid']);
            self::assertSame(1, $row['t3ver_wsid']);
            self::assertSame(1, $row['tx_container_parent']);
            self::assertSame(201, $row['colPos']);
        }
    }

    /**
     * @test
     */
    public function copyChildOutsideContainer(): void
    {
        $cmdmap = [
            'tt_content' => [
                2 => [
                    'copy' => [
                        'action' => 'paste',
                        'target' => 3,
                        'update' => [
                            'colPos' => 0,
                            'sys_language_uid' => 0

                        ]
                    ]
                ]
            ]
        ];
        $this->dataHandler->start([], $cmdmap, $this->backendUser);
        $this->dataHandler->process_cmdmap();

        // copied record is not modified
        $row = $this->fetchOneRecord('uid', 2);
        self::assertSame(1, $row['tx_container_parent']);
        self::assertSame(200, $row['colPos']);

        $queryBuilder = $this->getQueryBuilder();
        $rows = $queryBuilder->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    't3_origuid',
                    $queryBuilder->createNamedParameter(2, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    't3ver_oid',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchAll();
        self::assertSame(1, count($rows));
        foreach ($rows as $row) {
            self::assertSame(3, $row['pid']);
            self::assertSame(1, $row['t3ver_wsid']);
            self::assertSame(0, $row['tx_container_parent']);
            self::assertSame(0, $row['colPos']);
        }

        $queryBuilder = $this->getQueryBuilder();
        $rows = $queryBuilder->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    't3_origuid',
                    $queryBuilder->createNamedParameter(2, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->neq(
                    't3ver_oid',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchAll();
        self::assertSame(1, count($rows));
        foreach ($rows as $row) {
            self::assertSame(1, $row['t3ver_wsid']);
            self::assertSame(0, $row['tx_container_parent']);
            self::assertSame(0, $row['colPos']);
        }
    }

    /**
     * @test
     */
    public function copyChildsColPosInOtherContainer(): void
    {
        $cmdmap = [
            'tt_content' => [
                2 => [
                    'copy' => [
                        'action' => 'paste',
                        'target' => 1,
                        'update' => [
                            'colPos' => '91-201',
                            'sys_language_uid' => 0

                        ]
                    ]
                ]
            ]
        ];
        $this->dataHandler->start([], $cmdmap, $this->backendUser);
        $this->dataHandler->process_cmdmap();

        // copied record is not modified
        $row = $this->fetchOneRecord('uid', 2);
        self::assertSame(1, $row['tx_container_parent']);
        self::assertSame(200, $row['colPos']);

        $queryBuilder = $this->getQueryBuilder();
        $rows = $queryBuilder->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    't3_origuid',
                    $queryBuilder->createNamedParameter(2, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchAll();
        self::assertSame(2, count($rows));
        foreach ($rows as $row) {
            self::assertSame(1, $row['pid']);
            self::assertSame(1, $row['t3ver_wsid']);
            self::assertSame(91, $row['tx_container_parent']);
            self::assertSame(201, $row['colPos']);
        }
    }

    /**
     * @test
     */
    public function moveChildsColPosInOtherContainer(): void
    {
        $cmdmap = [
            'tt_content' => [
                2 => [
                    'move' => [
                        'action' => 'paste',
                        'target' => 1,
                        'update' => [
                            'colPos' => '91-201',
                            'sys_language_uid' => 0

                        ]
                    ]
                ]
            ]
        ];
        $this->dataHandler->start([], $cmdmap, $this->backendUser);
        $this->dataHandler->process_cmdmap();

        // copied record is not modified
        $row = $this->fetchOneRecord('uid', 2);
        self::assertSame(1, $row['tx_container_parent']);
        self::assertSame(200, $row['colPos']);

        $queryBuilder = $this->getQueryBuilder();
        $rows = $queryBuilder->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    't3_origuid',
                    $queryBuilder->createNamedParameter(2, \PDO::PARAM_INT)
                )
            )
            ->orWhere(
                $queryBuilder->expr()->eq(
                    't3ver_move_id',
                    $queryBuilder->createNamedParameter(2, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchAll();
        self::assertSame(2, count($rows));
        foreach ($rows as $row) {
            self::assertSame(1, $row['pid']);
            self::assertSame(1, $row['t3ver_wsid']);
            self::assertSame(91, $row['tx_container_parent']);
            self::assertSame(201, $row['colPos']);
        }
    }

    /**
     * @test
     */
    public function copyContainer(): void
    {
        $cmdmap = [
            'tt_content' => [
                1 => [
                    'copy' => [
                        'action' => 'paste',
                        'target' => 3,
                        'update' => [
                            'colPos' => 0
                        ]
                    ]
                ]
            ]
        ];
        $this->dataHandler->start([], $cmdmap, $this->backendUser);
        $this->dataHandler->process_cmdmap();

        // copied child is not modified
        $row = $this->fetchOneRecord('uid', 2);
        self::assertSame(1, $row['tx_container_parent']);
        self::assertSame(200, $row['colPos']);

        $queryBuilder = $this->getQueryBuilder();
        $containerRow = $queryBuilder->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    't3_origuid',
                    $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    't3ver_oid',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();
        self::assertIsArray($containerRow);
        $queryBuilder = $this->getQueryBuilder();
        $rows = $queryBuilder->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    't3_origuid',
                    $queryBuilder->createNamedParameter(2, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchAll();
        self::assertSame(2, count($rows));
        foreach ($rows as $row) {
            self::assertSame(3, $row['pid']);
            self::assertSame(1, $row['t3ver_wsid']);
            self::assertSame($containerRow['uid'], $row['tx_container_parent']);
            self::assertSame(200, $row['colPos']);
        }
    }

    /**
     * @test
     */
    public function moveRecordInColPosCreatesPlaceholderInContainer()
    {
        $cmdmap = [
            'tt_content' => [
                5 => [
                    'move' => [
                        'action' => 'paste',
                        'target' => 1,
                        'update' => [
                            'colPos' => '1-200',
                            'sys_language_uid' => 0

                        ]
                    ]
                ]
            ]
        ];
        $this->dataHandler->start([], $cmdmap, $this->backendUser);
        $this->dataHandler->process_cmdmap();
        $origFirstElement = $this->fetchOneRecord('uid', 2);
        // moved placeholder
        $placeHolderElement = $this->fetchOneRecord('t3ver_move_id', 5);
        self::assertSame(1, $placeHolderElement['tx_container_parent']);
        self::assertTrue($placeHolderElement['sorting'] < $origFirstElement['sorting']);
    }
}
